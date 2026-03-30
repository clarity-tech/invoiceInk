<?php

namespace App\Console\Commands;

use Aws\S3\S3Client;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:setup-storage')]
#[Description('Create S3 bucket and set public read policy for local development')]
class SetupStorage extends Command
{
    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('This command is for local development only.');

            return self::FAILURE;
        }

        $disk = config('filesystems.disks.s3');

        if (empty($disk['endpoint'])) {
            $this->warn('No S3 endpoint configured — skipping bucket setup.');
            $this->info('Set AWS_ENDPOINT in .env for local S3 (RustFS/MinIO).');

            return self::SUCCESS;
        }

        $bucket = $disk['bucket'];

        $this->info("Setting up S3 bucket: {$bucket}");
        $this->info("Endpoint: {$disk['endpoint']}");

        try {
            $client = new S3Client([
                'version' => 'latest',
                'region' => $disk['region'],
                'endpoint' => $disk['endpoint'],
                'use_path_style_endpoint' => true,
                'credentials' => [
                    'key' => $disk['key'],
                    'secret' => $disk['secret'],
                ],
            ]);

            if ($client->doesBucketExist($bucket)) {
                $this->info("Bucket '{$bucket}' already exists.");
            } else {
                $client->createBucket(['Bucket' => $bucket]);
                $this->info("Created bucket: {$bucket}");
            }

            $policy = json_encode([
                'Version' => '2012-10-17',
                'Statement' => [[
                    'Effect' => 'Allow',
                    'Principal' => '*',
                    'Action' => ['s3:GetObject'],
                    'Resource' => ["arn:aws:s3:::{$bucket}/*"],
                ]],
            ]);

            $client->putBucketPolicy(['Bucket' => $bucket, 'Policy' => $policy]);
            $this->info('Public read policy applied.');

        } catch (\Exception $e) {
            $this->error("Failed: {$e->getMessage()}");
            $this->warn('Is the storage service running? Try: sail up -d');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Storage setup complete.');

        return self::SUCCESS;
    }
}
