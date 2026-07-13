<?php

use App\Events\CustomEvent;
use App\Jobs\CustomJob;
use App\Listeners\CustomListener;
use App\Mail\CustomMail;
use App\Models\User;
use App\Notifications\CustomNotification;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Http\UploadedFile;


it('tests event', function () {
    Event::fake();
    $user = User::factory()->create();
    $this->get('/event/'.$user->id);

    Event::assertDispatched(CustomEvent::class, function ($event) use ($user) {
        return $event->user->is($user);
    });
    Event::assertListening(CustomEvent::class, CustomListener::class);
});

it('tests listener if queued', function () {    // Rarely used
    Queue::fake();
    $user = User::factory()->create();
    CustomEvent::dispatch($user);

    Queue::assertPushed(CallQueuedListener::class, function ($job) use ($user) {
        return $job->class === CustomListener::class;
    });
});

it('tests notification', function () {
    Notification::fake();
    $user = User::factory()->create();
    $this->get('/notify/'.$user->id);

    Notification::assertSentTo([$user], CustomNotification::class);
});

it('tests mail', function () {
    Mail::fake();
    $user = User::factory()->create();
    $this->get('/email/'.$user->id);

    /*Mail::assertSent(CustomMail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });*/
    Mail::assertQueued(CustomMail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
});

it('tests queue', function () {
    Queue::fake();
    $user = User::factory()->create();
    $this->get('/queue/'.$user->id);

    Queue::assertPushed(CustomJob::class, function ($job) use ($user) {
        return $job->user->is($user);
    });
});

it('tests file upload', function () {
    Storage::fake();
    $file = UploadedFile::fake()->image('test.jpg');
    //$file = UploadedFile::fake()->image('test.jpg')->size(10000);
    //$file = UploadedFile::fake()->create('test.pdf', 1000);

    $this->post('/upload', [
        'file' => $file,
    ])->assertOk();

    Storage::disk('public')->assertExists('uploads/' . $file->hashName());
});

it('tests the old file is deleted upon new upload', function () {
    Storage::fake();

    $oldFile = UploadedFile::fake()->image('test.jpg');
    $oldFilePath = $oldFile->store('uploads', 'public');
    Storage::disk('public')->assertExists($oldFilePath);

    $newFile = UploadedFile::fake()->image('test2.jpg');

    $response = $this->post('/upload', [
        'file' => $newFile,
        'oldFilePath' => $oldFilePath,     // This oldFilePath should be taken from database
    ])->assertOk();

    Storage::disk('public')->assertMissing($oldFilePath);

    expect($response->getContent())->toBe('uploads/' . $newFile->hashName());
    Storage::disk('public')->assertExists($response->getContent());
});




