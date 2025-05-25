<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# E-Commerce Backend - Laravel GraphQL API

A comprehensive e-commerce backend built with Laravel and GraphQL, featuring modern architecture and complete business functionality for online retail operations.

## 🚀 Project Overview

This is a full-featured e-commerce backend API built using:
- **Laravel Framework** - Robust PHP backend framework
- **GraphQL** - Modern API query language with Lighthouse GraphQL
- **JWT Authentication** - Secure token-based authentication
- **Role-based Access Control** - Multi-level user permissions
- **Payment Integration** - ZaloPay, VNPay, and Cash on Delivery
- **Shipping Integration** - GHN (Giao Hang Nhanh) shipping services
- **Email Verification** - Secure user verification system

## 🏗️ Architecture & Features

### Core Features

#### 🔐 Authentication & User Management
- **User Registration & Login** with email/phone
- **JWT Token Authentication** with refresh tokens
- **Email Verification** system
- **Password Reset** functionality
- **Role-based Access Control** (Admin, Staff, Customer)
- **User Profile Management**

#### 🛍️ Product Management
- **Complete Product CRUD** operations
- **Product Details** with specifications, images, keywords
- **Brand Management** system
- **Stock Management** with quantity tracking
- **Product Search & Filtering** with pagination
- **Product Status Management** (active/inactive)

#### 🛒 Shopping Cart
- **Add/Remove Items** from cart
- **Quantity Management**
- **Cart Persistence** per user
- **Clear Cart** functionality

#### 📦 Order Management
- **Order Creation** from cart or direct items
- **Order Status Tracking** (Pending → Confirmed → Shipped → Delivered)
- **Order History** for users
- **Order Cancellation**
- **Order Item Management** (update/delete individual items)

#### 💳 Payment Processing
- **Multiple Payment Methods**:
  - ZaloPay integration
  - VNPay integration  
  - Cash on Delivery (COD)
- **Payment Status Tracking**
- **IPN (Instant Payment Notification)** handling
- **Transaction Management**

#### 🚚 Shipping & Logistics
- **GHN Shipping Integration**
- **Address Management** (Province/District/Ward)
- **Shipping Fee Calculation**
- **Delivery Time Estimation**
- **Shipping Status Tracking**
- **Multiple Shipping Methods**

#### 🎫 Customer Support
- **Support Ticket System**
- **Ticket Responses** and threading
- **Status Management** (Open/In Progress/Resolved)
- **Admin and Customer interactions**

#### ⭐ Product Reviews
- **Customer Reviews** with ratings
- **Review Management** (CRUD operations)
- **Product Rating Aggregation**
- **Review Moderation**

#### 📊 Analytics & Metrics (Admin)
- **Sales Metrics** (daily/weekly/monthly)
- **Product Performance** analytics
- **User Growth** metrics
- **Support Ticket** analytics
- **Dashboard Overview** with KPIs

#### 🔍 Advanced Search
- **Smart Search** with natural language processing
- **Product Filtering** by multiple criteria
- **Search Metadata** and performance tracking
- **Filter Options** with counts

## 📋 API Documentation

### Authentication Endpoints

#### User Registration
```graphql
mutation {
  signup(
    email: "user@example.com"
    phone: "+1234567890"
    password: "securePassword123"
    full_name: "John Doe"
  ) {
    code
    message
    user {
      id
      email
      full_name
      role
    }
  }
}
```

#### User Login
```graphql
mutation {
  login(
    email: "user@example.com"
    password: "securePassword123"
  ) {
    code
    message
    user {
      id
      email
      full_name
    }
    access_token
    refresh_token
    expires_at
  }
}
```

#### Get Current User
```graphql
query {
  getCurrentUser {
    code
    message
    user {
      id
      email
      full_name
      role
      email_verified
    }
  }
}
```

### Product Management

#### Get Products with Pagination
```graphql
query {
  getPaginatedProducts(
    search: "laptop"
    status: "active"
    price_min: 100.0
    price_max: 2000.0
    sort_field: "price"
    sort_direction: "asc"
    page: 1
    per_page: 10
  ) {
    code
    message
    products {
      id
      name
      price
      stock
      details {
        description
        images
        specifications {
          name
          value
        }
      }
    }
    pagination {
      total
      current_page
      per_page
      has_more_pages
    }
  }
}
```

#### Create Product (Admin/Staff)
```graphql
mutation {
  createProduct(
    name: "Gaming Laptop"
    price: 1299.99
    stock: 50
    status: true
    brand_id: "1"
    weight: 2.5
    details: {
      description: "High-performance gaming laptop"
      specifications: [
        { name: "CPU", value: "Intel i7" }
        { name: "RAM", value: "16GB" }
      ]
      images: ["image1.jpg", "image2.jpg"]
      keywords: ["gaming", "laptop", "intel"]
    }
  ) {
    code
    message
    product {
      id
      name
      price
    }
  }
}
```

### Shopping Cart Operations

#### Add Item to Cart
```graphql
mutation {
  addCartItem(
    product_id: "1"
    quantity: 2
  ) {
    code
    message
  }
}
```

#### Get Cart Items
```graphql
query {
  getCartItems {
    code
    message
    cart_items {
      id
      quantity
      product {
        product_id
        name
        price
        image
        stock
      }
    }
  }
}
```

### Order Management

#### Create Order from Cart
```graphql
mutation {
  createOrderFromCart {
    code
    message
    order {
      id
      status
      total_price
      items {
        id
        product_id
        name
        price
        quantity
      }
    }
  }
}
```

#### Get User Orders
```graphql
query {
  getUserOrders {
    code
    message
    orders {
      id
      status
      created_at
      total_price
      payment_status
      shipping_address
    }
  }
}
```

#### Update Order Status (Admin)
```graphql
mutation {
  shipOrder(order_id: "123") {
    code
    message
  }
}
```

### Payment Processing

#### Create VNPay Payment
```graphql
mutation {
  createPaymentVNPay(
    order_id: "123"
    order_type: "billpayment"
    bank_code: "NCB"
    locale: "vn"
  ) {
    code
    message
    payment_url
  }
}
```

#### Create ZaloPay Payment
```graphql
mutation {
  createPaymentZalopay(
    order_id: "123"
  ) {
    code
    message
    payment_url
    transaction_id
  }
}
```

### Shipping Management

#### Calculate Shipping Fee
```graphql
query {
  calculateShippingFee(
    to_district_id: "1542"
    to_ward_code: "21211"
    weight: 500
    value: 100000
  ) {
    code
    message
    fee
    expected_delivery_time
  }
}
```

#### Create Shipping
```graphql
mutation {
  createShipping(
    order_id: "123"
    province_name: "Ho Chi Minh"
    district_name: "District 1"
    ward_name: "Ben Nghe Ward"
    address: "123 Nguyen Hue Street"
    recipient_name: "John Doe"
    recipient_phone: "+84123456789"
    shipping_method: "standard"
  ) {
    code
    message
    shipping {
      id
      estimated_date
      shipping_fee
    }
  }
}
```

### Support System

#### Create Support Ticket
```graphql
mutation {
  createSupportTicket(
    subject: "Product Issue"
    message: "I have an issue with my recent order"
  ) {
    code
    message
    supportTicket {
      id
      subject
      status
      created_at
    }
  }
}
```

### Reviews & Ratings

#### Create Product Review
```graphql
mutation {
  createReview(
    order_item_id: "456"
    rating: 5
    comment: "Excellent product quality!"
  ) {
    code
    message
  }
}
```

#### Get Product Reviews
```graphql
query {
  getProductReviews(product_id: "1") {
    code
    message
    reviews {
      id
      rating
      comment
      user {
        username
      }
      created_at
    }
    total
  }
}
```

### Admin Analytics

#### Dashboard Metrics
```graphql
query {
  getAdminDashboardMetrics {
    code
    message
    orders_today
    orders_week
    revenue_today
    revenue_week
    total_products
    low_stock_products
    total_users
    new_users_today
    support_tickets_open
  }
}
```

#### Sales Analytics
```graphql
query {
  getSalesMetrics(
    timeframe: "weekly"
    start_date: "2024-01-01"
    end_date: "2024-01-31"
  ) {
    code
    message
    weekly_metrics {
      date
      revenue
      orders_count
      average_order_value
    }
  }
}
```

### Smart Search

#### Natural Language Search
```graphql
query {
  smartSearch(
    query: "I need a powerful laptop for gaming under $1500"
  ) {
    code
    message
    products {
      id
      name
      price
      details {
        description
      }
    }
    total
    metadata {
      original_query
      interpreted_query
      processing_time_ms
    }
  }
}
```

## 🛠️ Installation & Setup

### Prerequisites
- PHP 8.1+
- Composer
- MySQL/PostgreSQL
- Node.js & NPM
- Redis (optional, for caching)

### Installation Steps

1. **Clone the Repository**
```bash
git clone <repository-url>
cd E-Commerce-BE-Laravel
```

2. **Install Dependencies**
```bash
composer install
npm install
```

3. **Environment Configuration**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Database Setup**
```bash
php artisan migrate
php artisan db:seed
```

5. **Generate JWT Secret**
```bash
php artisan jwt:secret
```

6. **Storage Link**
```bash
php artisan storage:link
```

7. **Start Development Server**
```bash
php artisan serve
```

### Environment Variables

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce_db
DB_USERNAME=root
DB_PASSWORD=

# JWT Configuration
JWT_SECRET=your_jwt_secret
JWT_TTL=60
JWT_REFRESH_TTL=20160

# Payment Gateways
ZALOPAY_APP_ID=your_zalopay_app_id
ZALOPAY_KEY1=your_zalopay_key1
ZALOPAY_KEY2=your_zalopay_key2

VNPAY_TMN_CODE=your_vnpay_tmn_code
VNPAY_HASH_SECRET=your_vnpay_hash_secret
VNPAY_URL=https://sandbox.vnpayment.vn/paymentv2/vpcpay.html

# GHN Shipping
GHN_API_URL=https://dev-online-gateway.ghn.vn/shiip/public-api
GHN_TOKEN=your_ghn_token
GHN_SHOP_ID=your_shop_id

# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
```

## 🔒 Security Features

- **JWT Authentication** with access and refresh tokens
- **Password Hashing** using bcrypt
- **Email Verification** for new accounts
- **Rate Limiting** on API endpoints
- **Input Validation** and sanitization
- **SQL Injection Protection** via Eloquent ORM
- **CORS Configuration** for cross-origin requests

## 📱 API Testing

### GraphQL Playground
Access the GraphQL playground at: `http://localhost:8000/graphql-playground`

### Example Headers for Protected Routes
```json
{
  "Authorization": "Bearer your_jwt_token_here"
}
```

## 🔄 Workflow Examples

### Complete Purchase Flow
1. User registers/logs in
2. Browse products with filtering
3. Add products to cart
4. Create order from cart
5. Select shipping method
6. Process payment (VNPay/ZaloPay/COD)
7. Track order status
8. Receive product and leave review

### Admin Management Flow
1. Admin login
2. View dashboard metrics
3. Manage products (CRUD)
4. Process orders (update status)
5. Handle support tickets
6. Monitor analytics

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License.

## 📞 Support

For support and questions:
- Create a support ticket through the API
- Email: support@yourcompany.com
- Documentation: [API Docs](http://localhost:8000/graphql-playground)

## 🚀 Deployment

### Production Checklist
- [ ] Configure production database
- [ ] Set up SSL certificates
- [ ] Configure production payment gateway credentials
- [ ] Set up monitoring and logging
- [ ] Configure email service
- [ ] Set up backup strategy
- [ ] Configure caching (Redis)
- [ ] Set up queue workers
- [ ] Configure file storage (S3/CloudFlare)

---

**Built with ❤️ using Laravel, GraphQL, and modern e-commerce best practices.**
