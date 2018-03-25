<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class YourNewsList extends Mailable
{
    use Queueable, SerializesModels;

    private $csv;
    private $datetime;

    /**
     * Create a new message instance.
     *
     * @param $csv
     * @internal param array $newsArray
     */
    public function __construct($csv, $datetime)
    {
        $this->csv = $csv;
        $this->datetime = $datetime;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('serpos95@gmail.com')
            ->view('emails.news_list')
            ->attachData($this->csv, 'news' . $this->datetime . '.csv', [
                'mime' => 'text/csv',
            ]);
    }
}
