<?php

declare(strict_types=1);

/**
 * NOTICE OF LICENSE.
 *
 * UNIT3D is open-sourced software licensed under the GNU Affero General Public License v3.0
 * The details is bundled with this project in the file LICENSE.txt.
 *
 * @project    UNIT3D
 *
 * @license    https://www.gnu.org/licenses/agpl-3.0.en.html/ GNU Affero General Public License v3.0
 * @author     HDVinnie
 */

namespace App\Jobs;

use App\Enums\GlobalRateLimit;
use App\Models\IgdbCompany;
use App\Models\IgdbGame;
use App\Models\IgdbGenre;
use App\Models\IgdbPlatform;
use App\Models\Torrent;
use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Throwable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use MarcReichel\IGDBLaravel\Models\Game;

class ProcessIgdbGameJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * ProcessIgdbGameJob constructor.
     */
    public function __construct(public int $id)
    {
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping((string) $this->id)->dontRelease()->expireAfter(30),
            new RateLimited(GlobalRateLimit::IGDB),
        ];
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): DateTime
    {
        return now()->addDay();
    }

    public function handle(): void
    {
        $fetchedGame = Game::select([
            'id',
            'name',
            'summary',
            'first_release_date',
            'url',
            'rating',
            'rating_count',
        ])
            ->with([
                'cover'                           => ['image_id'],
                'artworks'                        => ['image_id'],
                'genres'                          => ['id', 'name'],
                'videos'                          => ['video_id', 'name'],
                'involved_companies.company'      => ['id', 'name', 'url'],
                'involved_companies.company.logo' => ['image_id'],
                'platforms'                       => ['id', 'name'],
                'platforms.platform_logo'         => ['image_id']
            ])
            ->findOrFail($this->id);

        IgdbGame::query()->upsert([[
            'id'                     => $this->id,
            'name'                   => $fetchedGame['name'] ?? null,
            'summary'                => $fetchedGame['summary'] ?? '',
            'first_artwork_image_id' => $fetchedGame['artworks'][0]['image_id'] ?? null,
            'first_release_date'     => $fetchedGame['first_release_date'] ?? null,
            'cover_image_id'         => $fetchedGame['cover']['image_id'] ?? null,
            'url'                    => $fetchedGame['url'] ?? null,
            'rating'                 => $fetchedGame['rating'] ?? null,
            'rating_count'           => $fetchedGame['rating_count'] ?? null,
            'first_video_video_id'   => $fetchedGame['videos'][0]['video_id'] ?? null,
        ]], ['id']);

        $game = IgdbGame::query()->findOrFail($this->id);

        $genres = [];

        foreach ($fetchedGame->genres ?? [] as $genre) {
            if ($genre['id'] === null || $genre['name'] === null) {
                continue;
            }

            $genres[] = [
                'id'   => $genre['id'],
                'name' => $genre['name'],
            ];
        }

        IgdbGenre::query()->upsert($genres, ['id']);
        $game->genres()->sync(array_unique(array_column($genres, 'id')));

        $platforms = [];

        foreach ($fetchedGame->platforms ?? [] as $platform) {
            if ($platform['id'] === null || $platform['name'] === null) {
                continue;
            }

            $platforms[] = [
                'id'                     => $platform['id'],
                'name'                   => $platform['name'],
                'platform_logo_image_id' => $platform['platform_logo']['image_id'] ?? null,
            ];
        }

        IgdbPlatform::query()->upsert($platforms, ['id']);
        $game->platforms()->sync(array_unique(array_column($platforms, 'id')));

        $companies = [];

        foreach ($fetchedGame->involved_companies ?? [] as $company) {
            if ($company['company']['id'] === null || $company['company']['name'] === null) {
                continue;
            }

            $companies[] = [
                'id'            => $company['company']['id'],
                'name'          => $company['company']['name'],
                'url'           => $company['company']['url'] ?? null,
                'logo_image_id' => $company['company']['logo']['image_id'] ?? null,
            ];
        }

        IgdbCompany::query()->upsert($companies, ['id']);
        $game->companies()->sync(array_unique(array_column($companies, 'id')));

        $this->appendVideosToTorrentDescriptions($fetchedGame->videos ?? []);

        $this->applyCoversToTorrents($fetchedGame['cover']['image_id'] ?? null);

        // Although IGDB doesn't publicly state they cache their api responses,
        // use the same value as tmdb to not abuse them with too many requests

        cache()->put("igdb-game-scraper:{$this->id}", now(), 8 * 3600);
    }

    private function applyCoversToTorrents(?string $coverImageId): void
    {
        if ($coverImageId === null) {
            return;
        }

        $torrents = Torrent::query()
            ->where('igdb', '=', $this->id)
            ->whereRelation('category', 'game_meta', '=', true)
            ->get(['id']);

        if ($torrents->isEmpty()) {
            return;
        }

        try {
            $response = Http::timeout(20)
                ->accept('image/*')
                ->get('https://images.igdb.com/igdb/image/upload/t_original/'.$coverImageId.'.jpg');

            if (!$response->successful() || $response->body() === '') {
                return;
            }

            $imageData = $response->body();

            foreach ($torrents as $torrent) {
                $pathCover = Storage::disk('torrent-covers')->path('torrent-cover_'.$torrent->id.'.jpg');
                Image::make($imageData)->fit(400, 600)->encode('jpg', 90)->save($pathCover);
            }
        } catch (Throwable) {
            // Cover download failure is non-fatal; the job succeeds regardless
        }
    }

    /**
     * @param iterable<array<string, mixed>> $videos
     */
    private function appendVideosToTorrentDescriptions(iterable $videos): void
    {
        $videoIds = [];

        foreach ($videos as $video) {
            $videoId = $video['video_id'] ?? null;

            if (!\is_string($videoId)) {
                continue;
            }

            if (!preg_match('/^[a-zA-Z0-9_-]{11}$/', $videoId)) {
                continue;
            }

            $videoIds[] = $videoId;
        }

        $videoIds = array_values(array_unique($videoIds));

        if ($videoIds === []) {
            return;
        }

        $torrents = Torrent::query()
            ->where('igdb', '=', $this->id)
            ->whereRelation('category', 'game_meta', '=', true)
            ->get(['id', 'description']);

        foreach ($torrents as $torrent) {
            $description = $torrent->description ?? '';
            $newVideoBlocks = [];
            preg_match_all('/\[(?:youtube|video)(?:=&quot;youtube&quot;)?]([a-z0-9_-]{11})\[\/(?:youtube|video)]/i', $description, $matches);
            $existingVideoIds = array_map('strtolower', $matches[1] ?? []);

            foreach ($videoIds as $videoId) {
                if (in_array(strtolower($videoId), $existingVideoIds, true)) {
                    continue;
                }

                $newVideoBlocks[] = '[video]'.$videoId.'[/video]';
            }

            if ($newVideoBlocks === []) {
                continue;
            }

            $separator = trim($description) === '' ? '' : "\n\n";

            $torrent->forceFill([
                'description' => $description.$separator.implode("\n", $newVideoBlocks),
            ])->save();
        }
    }
}
