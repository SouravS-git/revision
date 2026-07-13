<?php

use App\Events\CustomEvent;
use App\Jobs\CustomJob;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('sendmail', function (){

    $user = \App\Models\User::first();

    // Using raw without any view
    \Illuminate\Support\Facades\Mail::raw('Test Email', function ($message) use ($user) {
        $message->to($user->email, $user->name)
            ->subject('Raw Email');
    });

    // Without using mailable class
    \Illuminate\Support\Facades\Mail::send('emails.test-mail', ['user' => $user], function ($message) use ($user) {
        $message->to($user->email, $user->name)
            ->subject('Without mailable class');
    });

    // Using mailable class with file attachment
    //\Illuminate\Support\Facades\Mail::to($user->email, $user->name)->send(new \App\Mail\CustomMail($user));
    //\Illuminate\Support\Facades\Mail::to($user->email, $user->name)->queue(new \App\Mail\CustomMail($user));
    \Illuminate\Support\Facades\Mail::to($user->email, $user->name)->later(now()->addSecond(30), new \App\Mail\CustomMail($user));

    // Using notification and mailable class with file attachment
    $user->notify(new \App\Notifications\CustomNotification($user));

});

// File Handling
Route::get('/temporaryUrl', function () {
    $article = Article::find(64);
    return Storage::disk('s3')->temporaryUrl($article->thumbnail, now()->addMinutes(1));
});

Route::get('/download', function () {
    $article = Article::find(65);
    return Storage::disk('local')->download($article->thumbnail);
});

// PEST Testing -
    // Pest Test For Event
    Route::get('event/{user}', function (User $user){
        CustomEvent::dispatch($user);
    });

    // Pest Test For Notification
    Route::get('notify/{user}', function (User $user){
        $user->notify(new \App\Notifications\CustomNotification($user));
    });

    // Pest Test For Email
    Route::get('email/{user}', function (User $user){
        Mail::to($user)->queue(new \App\Mail\CustomMail($user));
    });

    // Pest test for Queue
    Route::get('queue/{user}', function (User $user){
        CustomJob::dispatch($user);
    });

    // Pest test for File Upload
    Route::post('upload', function (Request $request){
        $request->validate([
            'file' => 'required|file|mimes:jpg|max:2048',
        ]);

        if ($request->has('oldFilePath'))
            Storage::disk('public')->delete($request->oldFilePath);    // This oldFilePath should be taken from database

        $file = $request->file('file');
        return $file->store('uploads', 'public');
    });
