<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Storage;

/**
 * Keeps sensitive uploads on the private local disk and retires legacy public copies.
 */
class PrivateDocumentStorage
{
    public function isSensitivePath(string $path): bool
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        return str_starts_with($path, 'users/cnic/')
            || str_starts_with($path, 'online-orders/customer-receipts/');
    }

    /**
     * Ensure a sensitive file exists on the private disk. When a verified public
     * original remains, delete it so /storage/... cannot expose the document.
     */
    public function ensurePrivate(string $path, bool $deletePublicWhenVerified = true): bool
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        if ($path === '' || ! $this->isSensitivePath($path)) {
            return false;
        }

        $local = Storage::disk('local');
        $public = Storage::disk('public');
        $localExists = $local->exists($path);
        $publicExists = $public->exists($path);

        if (! $localExists && $publicExists) {
            $input = $public->readStream($path);

            if (! is_resource($input)) {
                return false;
            }

            $written = $local->writeStream($path, $input);
            fclose($input);

            if (! $written) {
                return false;
            }

            $localExists = $local->exists($path);
        }

        if (! $localExists) {
            return false;
        }

        if ($deletePublicWhenVerified && $publicExists && $this->copiesMatch($path)) {
            $public->delete($path);
        }

        return true;
    }

    public function copiesMatch(string $path): bool
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        $local = Storage::disk('local');
        $public = Storage::disk('public');

        if (! $local->exists($path) || ! $public->exists($path)) {
            return false;
        }

        if ($local->size($path) !== $public->size($path)) {
            return false;
        }

        $localStream = $local->readStream($path);
        $publicStream = $public->readStream($path);

        if (! is_resource($localStream) || ! is_resource($publicStream)) {
            if (is_resource($localStream)) {
                fclose($localStream);
            }
            if (is_resource($publicStream)) {
                fclose($publicStream);
            }

            return false;
        }

        $localHash = hash_init('sha256');
        $publicHash = hash_init('sha256');
        hash_update_stream($localHash, $localStream);
        hash_update_stream($publicHash, $publicStream);
        fclose($localStream);
        fclose($publicStream);

        return hash_final($localHash) === hash_final($publicHash);
    }
}
