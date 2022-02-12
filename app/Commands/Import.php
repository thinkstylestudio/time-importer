<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use LaravelZero\Framework\Commands\Command;

class Import extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'import';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Import CSV to Api Endpoint';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $data = $this->csvToCollection('timesheet.csv');

        $keys = $data->keys();
        $data->forget($keys->first());
        $data->forget($keys->last());

        $this->info('Importing CSV to Api Endpoint');

        foreach ($data as $key => $row) {
            $this->info('Importing: ' . $key);

            $date = Carbon::createFromFormat('d/M/y h:i A', $row[4]);

            $timeEntry = [
                'date' => $date->format('m/d/Y'),
                'duration' => gmdate('H:i', floor($row[6] * 3600)),
                'task_id' => $row[2],
                'notes' => $row[3],
                'task' => 'software_development',
                'project' => 269,
            ];

            $response = Http::withToken(env('API_TOKEN'))
                ->post(
                    env('ENDPOINT') . '/time_entries',
                    $timeEntry
                );

            $this->info('Response: ' . $response->getStatusCode());
        }
    }

    /**
     * Define the command's schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    public
    function schedule(
        Schedule $schedule
    ): void {
        // $schedule->command(static::class)->everyMinute();
    }

    /**
     * Convert CSV to array.
     *
     * @param string $csv
     * @return Collection
     */
    private
    function csvToCollection(
        $csv
    ) {
        $data = [];

        if (($handle = fopen($csv, 'rb')) !== false) {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $data[] = $row;
            }
            fclose($handle);
        }

        return collect($data);
    }
}
