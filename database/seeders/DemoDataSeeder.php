<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@usp.com.pk',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        // Create regular user
        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        // Create Categories
        $webDev = ServiceCategory::create([
            'name' => 'Web Development',
            'slug' => 'web-development',
            'description' => 'Custom web development services',
            'icon' => '🌐',
            'is_active' => true,
        ]);

        $mobileDev = ServiceCategory::create([
            'name' => 'Mobile Development',
            'slug' => 'mobile-development',
            'description' => 'iOS and Android app development',
            'icon' => '📱',
            'is_active' => true,
        ]);

        $design = ServiceCategory::create([
            'name' => 'UI/UX Design',
            'slug' => 'ui-ux-design',
            'description' => 'Professional design services',
            'icon' => '🎨',
            'is_active' => true,
        ]);

        $cloud = ServiceCategory::create([
            'name' => 'Cloud Services',
            'slug' => 'cloud-services',
            'description' => 'Cloud infrastructure and deployment',
            'icon' => '☁️',
            'is_active' => true,
        ]);

        // Create Services
        Service::create([
            'category_id' => $webDev->id,
            'title' => 'E-Commerce Website Development',
            'slug' => 'ecommerce-website-development',
            'short_description' => 'Build a fully functional online store with payment integration',
            'description' => 'We create powerful e-commerce websites that drive sales. Our solutions include product management, shopping cart, payment gateway integration, order management, and customer accounts. Perfect for businesses looking to sell online.',
            'price' => 2500.00,
            'price_unit' => 'per project',
            'features' => [
                'Responsive design for all devices',
                'Payment gateway integration',
                'Product catalog management',
                'Shopping cart functionality',
                'Order tracking system',
                'Admin dashboard',
            ],
            'delivery_days' => 30,
            'is_featured' => true,
            'is_active' => true,
        ]);

        Service::create([
            'category_id' => $webDev->id,
            'title' => 'Custom Web Application',
            'slug' => 'custom-web-application',
            'short_description' => 'Tailored web applications for your business needs',
            'description' => 'Get a custom-built web application designed specifically for your business processes. We use modern frameworks and best practices to deliver scalable, secure, and maintainable solutions.',
            'price' => 5000.00,
            'price_unit' => 'per project',
            'features' => [
                'Custom functionality',
                'Database design',
                'API integration',
                'User authentication',
                'Role-based access control',
                '6 months support',
            ],
            'delivery_days' => 45,
            'is_featured' => true,
            'is_active' => true,
        ]);

        Service::create([
            'category_id' => $mobileDev->id,
            'title' => 'iOS & Android App Development',
            'slug' => 'ios-android-app-development',
            'short_description' => 'Native mobile apps for iOS and Android platforms',
            'description' => 'Professional mobile app development for both iOS and Android. We create engaging, high-performance apps that provide excellent user experience across all devices.',
            'price' => 8000.00,
            'price_unit' => 'per project',
            'features' => [
                'Native iOS and Android apps',
                'Push notifications',
                'Offline functionality',
                'App Store submission',
                'Backend API development',
                '3 months support',
            ],
            'delivery_days' => 60,
            'is_featured' => true,
            'is_active' => true,
        ]);

        Service::create([
            'category_id' => $design->id,
            'title' => 'UI/UX Design Package',
            'slug' => 'ui-ux-design-package',
            'short_description' => 'Complete design solution for web and mobile',
            'description' => 'Our comprehensive UI/UX design package includes user research, wireframing, prototyping, and high-fidelity designs. We focus on creating intuitive and beautiful interfaces.',
            'price' => 1500.00,
            'price_unit' => 'per project',
            'features' => [
                'User research and personas',
                'Wireframes and prototypes',
                'High-fidelity designs',
                'Design system',
                'Unlimited revisions',
                'Source files included',
            ],
            'delivery_days' => 20,
            'is_featured' => false,
            'is_active' => true,
        ]);

        Service::create([
            'category_id' => $cloud->id,
            'title' => 'Cloud Infrastructure Setup',
            'slug' => 'cloud-infrastructure-setup',
            'short_description' => 'AWS, Azure, or Google Cloud deployment',
            'description' => 'Set up your application infrastructure on leading cloud platforms. We handle server configuration, deployment automation, security, and monitoring.',
            'price' => 1200.00,
            'price_unit' => 'per project',
            'features' => [
                'Cloud platform setup',
                'CI/CD pipeline',
                'SSL certificate',
                'Database configuration',
                'Backup solutions',
                'Monitoring setup',
            ],
            'delivery_days' => 7,
            'is_featured' => false,
            'is_active' => true,
        ]);

        Service::create([
            'category_id' => $webDev->id,
            'title' => 'Website Maintenance',
            'slug' => 'website-maintenance',
            'short_description' => 'Monthly website maintenance and updates',
            'description' => 'Keep your website running smoothly with our monthly maintenance service. Includes updates, security patches, backups, and technical support.',
            'price' => 299.00,
            'price_unit' => 'per month',
            'features' => [
                'Weekly backups',
                'Security updates',
                'Performance optimization',
                'Content updates',
                'Technical support',
                'Monthly reports',
            ],
            'delivery_days' => null,
            'is_featured' => false,
            'is_active' => true,
        ]);
    }
}
