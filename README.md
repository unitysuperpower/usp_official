# USP Technology Company - Complete Tech Services Website

A fully dynamic, responsive tech solution website built with Laravel, React, and Tailwind CSS featuring service management, contact system, and live chat.

## Features

### Public Features
- **Beautiful Homepage**: Hero section, featured services, categories, and benefits
- **Services Listing**: Filterable and searchable services with pagination
- **Service Details**: Complete service information with contact form
- **Responsive Design**: Fully mobile-friendly using Tailwind CSS
- **Contact System**: Service request forms for each service

### Admin Panel
- **Dashboard**: Statistics overview and recent requests
- **Category Management**: Full CRUD operations for service categories
- **Service Management**: Create, update, delete services with images and features
- **Request Management**: View and manage service requests with status updates
- **Admin Authentication**: Protected admin routes with middleware

### Live Chat System
- **Floating Chat Button**: Visible only to logged-in users
- **Real-time Messaging**: React chat interface with polling and optional WebSocket updates
- **Message History**: Persistent conversation storage
- **Admin/User Distinction**: Different message styles for admins and users
- **Auto-scroll**: Automatically scrolls to latest messages

## Technology Stack

- **Backend**: Laravel 12
- **Frontend**: React 19, Tailwind CSS, Blade page shell
- **Real-time**: Laravel Echo / Pusher, with polling fallback
- **Database**: MySQL/SQLite
- **Authentication**: Laravel Fortify
- **Assets**: Vite

## Installation

1. **Clone the repository** (if applicable)

2. **Install dependencies**:
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**:
   - Configure your database in `.env`
   - Run migrations and seeders:
   ```bash
   php artisan migrate
   php artisan db:seed --class=DemoDataSeeder
   ```

5. **Storage link**:
   ```bash
   php artisan storage:link
   ```

6. **Start development servers**:
   ```bash
   # Terminal 1 - Laravel server
   php artisan serve

   # Terminal 2 - Vite dev server
   npm run dev
   ```

7. **Access the application**:
   - Public site: http://localhost:8000
   - Admin panel: http://localhost:8000/admin/dashboard

## Local demo credentials

Only run `DemoDataSeeder` in a disposable local database. For a real installation, skip demo seeding and create your administrator with `php artisan admin:create`.

### Admin Account
- Email: `admin@usp.com.pk`
- Password: `password`

### Regular User
- Email: `john@example.com`
- Password: `password`

## Database Structure

### Tables
- `users` - User accounts with admin flag
- `service_categories` - Service category management
- `services` - Service listings with pricing and features
- `service_requests` - Customer service inquiries
- `conversations` - Chat conversations
- `messages` - Chat messages

## Usage Guide

### Admin Panel
1. Login with admin credentials
2. Navigate to Admin Panel from the navigation
3. Manage categories, services, and requests
4. Update request statuses and add admin notes

### Creating Services
1. Go to Admin > Services > Add New Service
2. Fill in all required fields
3. Add features (one per line)
4. Upload an image (optional)
5. Mark as featured to display on homepage

### Live Chat (User)
1. Login as a regular user
2. Click the floating chat button (bottom right)
3. Type messages and chat with support
4. Chat history persists across sessions

### Service Requests
1. Browse services on the public site
2. Click "View Details" on any service
3. Fill out the contact form
4. Admin can view and manage requests in admin panel

## Routes

### Public Routes
- `/` - Homepage
- `/services` - Services listing
- `/services/{slug}` - Service details
- `/login` - Login page
- `/register` - Registration page

### Admin Routes (Protected)
- `/admin/dashboard` - Admin dashboard
- `/admin/categories` - Category management
- `/admin/services` - Service management
- `/admin/requests` - Request management

## Customization

### Adding New Service Categories
1. Admin > Categories > Add New Category
2. Add name, description, and emoji icon
3. Set as active

### Modifying Design
- Public pages and homepage: `resources/js/react/public.jsx`
- Admin content: `resources/js/react/admin.jsx`
- Leads and project workspaces: `resources/js/react/projects.jsx`
- Shared forms and tables: `resources/js/react/ui.jsx`
- Styles: `resources/css/react.css`
- Blade shell: `resources/views/react.blade.php`

`resources/views/welcome.blade.php` is a legacy template and does not control the homepage.

### Extending Chat Features
- Floating chat: `resources/js/react/floating-chat.jsx`
- Chat endpoints: `app/Http/Controllers/ChatController.php` and `app/Http/Controllers/Admin/ChatController.php`

## Production Deployment

1. **Build assets**:
   ```bash
   npm run build
   ```

2. **Optimize**:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

3. **Set permissions**:
   ```bash
   chmod -R 755 storage bootstrap/cache
   ```

4. **Configure environment**:
   - Set `APP_ENV=production`
   - Set `APP_DEBUG=false`
   - Configure proper database credentials
   - Set up queue workers for better performance (optional)

## Features Checklist

✅ Fully dynamic service management  
✅ Admin CRUD for services and categories  
✅ User can choose services and contact  
✅ Fully responsive design with Tailwind CSS  
✅ Beautiful, modern UI  
✅ Live chat system with floating button  
✅ Chat only visible when logged in  
✅ Request management system  
✅ Service pricing and features  
✅ Image uploads for services  
✅ Search and filter functionality  
✅ Status management for requests  
✅ Demo data seeder  

## Support

For issues or questions about this project, please check the Laravel and Livewire documentation:
- Laravel: https://laravel.com/docs
- Livewire: https://livewire.laravel.com/docs
- Tailwind CSS: https://tailwindcss.com/docs

## License

This is a custom-built application. Please refer to your organization's licensing terms.

## Verification and delivery

Run `php artisan test --compact` and `npm run build` before delivery.

The app also includes lead assignment and follow-ups, lead-to-project conversion,
customer project workspaces, milestone approvals, private file sharing, blog
engagement, two-factor settings, and SEO endpoints. Admin services, articles,
and categories support server-side search and pagination.

See `REACT_FRONTEND.md`, `SEO_GUIDE.md`, and `DEPLOYMENT_GUIDE.md` for deployment
and integration setup. Default mail and broadcast drivers write to logs; real
email, WebSocket, and browser push delivery require configured providers and
verification in the target environment.
