<?php

namespace App\Console\Commands;

use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    protected $signature = 'app:generate-sitemap';
    protected $description = 'Generate the sitemap for the application';

    public function handle()
    {
        $sitemap = Sitemap::create();

        // Add default routes
        $defaultRoutes = [
            '/',
            '/profile/',
            '/merchants/',
            '/merchants/:id/:name',
            '/join/',
            '/contact-us/',
            '/merchant/result/city',
            '/merchant/result/province',
            '/join-the-fun/raffle',
            '/join-the-fun/points',
            '/join-the-fun/events',
            '/scanner/',
            '/login/',
            '/forgot-password/',
            '/tripidkard/',
        ];

        // Add admin routes
        $adminRoutes = [
            '/admin/login/',
            '/admin/dashboard/',
            '/admin/enterprise/',
            '/admin/enterprise/register',
            '/admin/enterprise/:id/edit',
            '/admin/enterprise/archive',
            '/admin/merchant/',
            '/admin/merchant-pending/',
            '/admin/merchant/register',
            '/admin/merchant/:id/edit',
            '/admin/merchant/archive',
            '/admin/tripidkard/',
            '/admin/tripidkard/register',
            '/admin/customer/',
            '/admin/customer/register',
            '/admin/customer/:id/edit',
            '/admin/loyalty-stars',
            '/admin/loyalty-stars/register',
            '/admin/loyalty-stars/:id/edit',
            '/admin/stars/register',
            '/admin/loyalty-stars/:id/customer',
            '/admin/activity-log/',
        ];

        // Add merchant routes
        $merchantRoutes = [
            '/merchant/dashboard',
            '/merchant/scanner',
            '/merchant/tripidkard/',
            '/merchant/customer/',
            '/merchant/customer/register',
            '/merchant/customer/:id/edit',
            '/merchant/loyalty-stars',
            '/merchant/loyalty-stars/register',
            '/merchant/loyalty-stars/:id/edit',
            '/merchant/activity-log/',
            '/merchant/profile/',
        ];

        $allRoutes = array_merge($defaultRoutes, $adminRoutes, $merchantRoutes);

        foreach ($allRoutes as $route) {
            $sitemap->add(Url::create($route));
        }

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap generated successfully.');
    }
}
