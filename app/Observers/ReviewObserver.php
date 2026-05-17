<?php

namespace App\Observers;

use App\Models\Review;

use App\Mail\Customer\ReviewApprovedMail;
use Illuminate\Support\Facades\Mail;

class ReviewObserver
{
    /**
     * Handle the Review "created" event.
     */
    public function created(Review $review): void
    {
        //
    }

    /**
     * Handle the Review "updated" event.
     */
    public function updated(Review $review): void
    {
        if ($review->isDirty('status') && $review->status === 'approved') {
            $email = $review->user?->email;
            if ($email && setting('email.notify_review_approved', true)) {
                Mail::to($email)->queue(new ReviewApprovedMail($review));
            }
        }
    }

    /**
     * Handle the Review "deleted" event.
     */
    public function deleted(Review $review): void
    {
        //
    }

    /**
     * Handle the Review "restored" event.
     */
    public function restored(Review $review): void
    {
        //
    }

    /**
     * Handle the Review "force deleted" event.
     */
    public function forceDeleted(Review $review): void
    {
        //
    }
}
