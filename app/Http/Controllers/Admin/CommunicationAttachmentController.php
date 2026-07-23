<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Communication\Attachment;
use App\Models\User;
use App\Services\Communication\CommunicationAuthorizer;
use App\Services\Communication\Support\ActorRef;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommunicationAttachmentController extends Controller
{
    public function show(Request $request, Attachment $attachment, CommunicationAuthorizer $authorizer): StreamedResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $attachment->loadMissing('message.conversation.channel');
        $conversation = $attachment->message?->conversation;

        if ($conversation === null) {
            abort(404);
        }

        $authorizer->assertCanViewConversation($conversation, ActorRef::user($user->id));

        $disk = Storage::disk($attachment->disk);

        if (! $disk->exists($attachment->path)) {
            abort(404);
        }

        return $disk->download($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime,
        ]);
    }
}
