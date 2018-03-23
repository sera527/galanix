<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Carbon\Carbon;

class YourNewsList extends Mailable
{
    use Queueable, SerializesModels;

    private $csv;

    /**
     * Create a new message instance.
     *
     * @param $csv
     * @internal param array $newsArray
     */
    public function __construct($csv)
    {
        $this->csv = $csv;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $current = Carbon::now();
        return $this->from('serpos95@gmail.com')
            ->view('emails.news_list')
            ->attachData($this->csv, 'news' . $current->format('Y-m-d_H:i:s') . '.csv', [
                'mime' => 'text/csv',
            ]);
    }
}
