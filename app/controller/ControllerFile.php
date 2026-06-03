<?php
namespace App\controller;

use Src\classes\ClassAuth;
use Src\classes\ClassAccess;
use App\model\Request;
use App\model\RequestChatMessage;
use App\model\Log;

class ControllerFile
{
    private function notFound(): void
    {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Not found';
        exit;
    }

    private function forbidden(): void
    {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Forbidden';
        exit;
    }

    private function normalizePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        $path = ltrim($path, '/');
        return $path;
    }

    private function isSafeRelativePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }
        if (strpos($path, "\0") !== false) {
            return false;
        }
        if (strpos($path, '..') !== false) {
            return false;
        }
        return true;
    }

    private function requireChatAttachmentAccess(array $user, string $path): void
    {
        // Admin support / super admin can always access chat attachments.
        if (ClassAccess::can('requests.manage', $user) || ClassAccess::isSuperAdmin($user)) {
            return;
        }

        $msg = RequestChatMessage::findByAttachmentPath($path);
        if (!$msg) {
            $this->notFound();
        }

        $requestId = (int) ($msg['request_id'] ?? 0);
        if ($requestId <= 0) {
            $this->forbidden();
        }

        $ctx = Request::getByIdWithContext($requestId);
        if (!$ctx) {
            $this->notFound();
        }

        $isRequester = (int) ($ctx['requester_id'] ?? 0) === (int) ($user['id'] ?? 0);
        $isOwner = (int) ($ctx['owner_id'] ?? 0) === (int) ($user['id'] ?? 0);
        if (!$isRequester && !$isOwner) {
            $this->forbidden();
        }
    }

    private function requireFinanceAccess(array $user): void
    {
        if (ClassAccess::can('payments.manage', $user) || ClassAccess::isSuperAdmin($user)) {
            return;
        }
        $this->forbidden();
    }

    private function serveFileFromAbsolute(string $absPath, string $downloadName = '', bool $forceDownload = false): void
    {
        if (!is_file($absPath)) {
            $this->notFound();
        }

        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
        $mime = $finfo ? (string) finfo_file($finfo, $absPath) : 'application/octet-stream';
        if ($finfo) {
            finfo_close($finfo);
        }

        header('X-Content-Type-Options: nosniff');
        header('Content-Type: ' . ($mime !== '' ? $mime : 'application/octet-stream'));
        header('Content-Length: ' . (string) filesize($absPath));

        $name = $downloadName !== '' ? $downloadName : basename($absPath);
        $disposition = $forceDownload ? 'attachment' : ((strpos($mime, 'image/') === 0) ? 'inline' : 'attachment');
        header('Content-Disposition: ' . $disposition . '; filename="' . str_replace('"', '', $name) . '"');

        // Avoid caching sensitive files in shared caches.
        header('Cache-Control: private, no-store, max-age=0');

        readfile($absPath);
        exit;
    }

    private function auditFileAccess(array $user, string $path, string $mode, bool $forceDownload): void
    {
        try {
            Log::create([
                'user_id' => (int) ($user['id'] ?? 0),
                'action' => $forceDownload ? 'file_download' : 'file_view',
                'entity_type' => 'file',
                'entity_id' => null,
                'details' => json_encode([
                    'path' => $path,
                    'mode' => $mode,
                    'download' => $forceDownload ? 1 : 0,
                    'ua' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
                    'route' => (string) ($_SERVER['REQUEST_URI'] ?? ''),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        } catch (\Throwable $e) {
            // Logging must not block file access.
        }
    }

    /**
     * Serve protected uploads.
     * Example: /file/serve?path=public/storage/uploads/boost_proofs/boost_...
     */
    public function serve(): void
    {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();
        if (!is_array($user)) {
            $this->forbidden();
        }

        $path = $this->normalizePath((string) ($_GET['path'] ?? ''));
        if (!$this->isSafeRelativePath($path)) {
            $this->notFound();
        }

        $allowedPrefixes = [
            'public/storage/uploads/boost_proofs/' => 'finance',
            'public/storage/uploads/trust_badge_proofs/' => 'finance',
            'public/storage/uploads/subscription_proofs/' => 'finance',
            'public/storage/uploads/commission_proofs/' => 'finance',
            'public/storage/uploads/commission_payout_proofs/' => 'finance',
            'public/storage/uploads/request_chat_attachments/' => 'chat',
        ];

        $mode = null;
        foreach ($allowedPrefixes as $prefix => $prefixMode) {
            if (strpos($path, $prefix) === 0) {
                $mode = $prefixMode;
                break;
            }
        }

        if ($mode === null) {
            $this->notFound();
        }

        $forceDownload = isset($_GET['download']) && (string) $_GET['download'] === '1';

        if ($mode === 'finance') {
            $this->requireFinanceAccess($user);
        } elseif ($mode === 'chat') {
            $this->requireChatAttachmentAccess($user, $path);
        } else {
            $this->forbidden();
        }

        $abs = rtrim((string) DIRREQ, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        $real = realpath($abs);
        $root = realpath((string) DIRREQ);
        if ($real === false || $root === false || strpos($real, $root) !== 0) {
            $this->notFound();
        }

        $this->auditFileAccess($user, $path, (string) $mode, $forceDownload);
        $this->serveFileFromAbsolute($real, '', $forceDownload);
    }
}

