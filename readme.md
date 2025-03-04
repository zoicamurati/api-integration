/ README.md
# Real Estate API Integration

A Symfony-based service that integrates with multiple real estate APIs, normalizes data, and stores it in a database.

## Features

- Fetches property data from Sprengnetter and Europace APIs
- Normalizes data structure for consistent information
- Implements Redis caching to prevent redundant API requests
- Stores fetched property data in MySQL database using Doctrine ORM
- Handles API failures with retries and logging
- Provides RESTful API to access the stored data

## Setup Instructions

### Prerequisites

- PHP 8.1 or higher
- Composer
- MySQL
- Redis

### Installation

1. Clone the repository

2. Install dependencies
```bash
composer install
```

3. Configure environment variables in `.env.local`
```
DATABASE_URL="mysql://user:password@127.0.0.1:3306/real_estate"
REDIS_URL=redis://localhost:6379
```

4. Create database and run migrations
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. Run the property sync command
```bash
php bin/console sync:properties
```

## API Endpoints

- `GET /api/properties` - List all properties (with pagination)
- `GET /api/properties/{id}` - Get a specific property

## Design Decisions

1. **API Client Abstraction**: Used an interface to standardize interactions with different API providers, making it easy to add new sources.

2. **Normalization**: Each API client normalizes its data to a common format before storage, ensuring consistency.

3. **Caching Strategy**: Implemented Redis caching with a 1-hour expiration to reduce unnecessary API calls.

4. **Batch Processing**: Used batch processing when storing entities to improve performance with large datasets.

5. **Error Handling**: Implemented a retry mechanism for API failures, with detailed logging.

6. **Tagged Services**: Used Symfony's tagged service pattern to collect all API clients.

