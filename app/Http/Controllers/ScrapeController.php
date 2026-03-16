<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Scopes\ApprovedScope;
use App\Models\Torrent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ScrapeController extends Controller
{
    private const HEADERS = [
        'Content-Type'  => 'text/plain; charset=utf-8',
        'Cache-Control' => 'private, no-cache, no-store, must-revalidate, max-age=0',
        'Pragma'        => 'no-cache',
        'Expires'       => 0,
        'Connection'    => 'close',
    ];

    public function index(Request $request, string $passkey): Response
    {
        if (!$this->isValidPasskey($passkey)) {
            return $this->response($this->failure('Passkey format is incorrect'));
        }

        $user = User::query()
            ->select(['id'])
            ->where('passkey', '=', $passkey)
            ->first();

        if ($user === null) {
            return $this->response($this->failure('Unknown passkey'));
        }

        $infoHashes = $this->extractInfoHashes($request);

        if ($infoHashes === []) {
            return $this->response($this->failure('Missing info_hash'));
        }

        return $this->response($this->scrapePayload($infoHashes));
    }

    private function extractInfoHashes(Request $request): array
    {
        $rawQuery = (string) $request->server->get('QUERY_STRING', '');

        if ($rawQuery === '') {
            return [];
        }

        $hashes = [];

        preg_match_all('/(?:^|&)info_hash=([^&]*)/', $rawQuery, $matches);

        foreach ($matches[1] as $encodedHash) {
            $decoded = urldecode($encodedHash);

            if (\strlen($decoded) === 20) {
                $hashes[] = $decoded;

                continue;
            }

            if (\strlen($decoded) === 40 && ctype_xdigit($decoded)) {
                $binaryHash = hex2bin($decoded);

                if ($binaryHash !== false) {
                    $hashes[] = $binaryHash;
                }
            }
        }

        return array_values(array_unique($hashes));
    }

    private function scrapePayload(array $infoHashes): string
    {
        $payload = 'd5:filesd';

        foreach ($infoHashes as $infoHash) {
            $torrent = Torrent::withoutGlobalScope(ApprovedScope::class)
                ->select(['seeders', 'times_completed', 'leechers'])
                ->where('info_hash', '=', $infoHash)
                ->first();

            $complete = $torrent?->seeders ?? 0;
            $downloaded = $torrent?->times_completed ?? 0;
            $incomplete = $torrent?->leechers ?? 0;

            $payload .= '20:'.$infoHash.'d8:completei'.$complete.'e10:downloadedi'.$downloaded.'e10:incompletei'.$incomplete.'ee';
        }

        return $payload.'ee';
    }

    private function isValidPasskey(string $passkey): bool
    {
        return \strlen($passkey) === 32
            && strspn(strtolower($passkey), 'abcdef0123456789') === 32;
    }

    private function failure(string $message): string
    {
        return 'd14:failure reason'.\strlen($message).':'.$message.'e';
    }

    private function response(string $content): Response
    {
        return response($content, 200, self::HEADERS);
    }
}