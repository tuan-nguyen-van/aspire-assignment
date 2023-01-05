## Setup environment on local 

- You must have docker, php >= 8.0, composer installed on your machine to run below commands


- You should set alias inside .bashrc or .zshrc: 
```
alias sail='./vendor/bin/sail'
```

1. Install dependencies
```
composer install
```

2. Set up the .env file:
```
cp .env.example .env
```

3. Start up the docker services
```
sail up
```

4. Install npm dependencies
```
sail npm install
```

5. Create new encryption key
```
sail artisan key:generate
```

6. Run migrations and seeding
```
sail artisan migrate --seed
```

7. Run tests
```
sail artisan test
```

## Usage guide
- We should use SQL databases because we use Laravel and we have a lot of transactions and the type of all table columns are known.

-   Database design [database-diagram.pdf](https://github.com/tuan-nguyen-van/jitera-coding-test/blob/develop/database-diagram.pdf)

-   Use Laravel/Sanctum package to issue and manage API tokens

-   To create an API token, first send a post request to route '/api/token/create' with body {"email": "...","password": "password"} then you will get a $token return.

- Then to create HTTP request to api routes inside /routes/api.php you must set header with 'Authorization' equal "Bearer $token"

- The sample Postman file for you to import is [here](https://github.com/tuan-nguyen-van/jitera-coding-test/blob/develop/postman_collection.json)

- Use husky npm package to run /.husky/pre-commit commands to run code checking, code fixing before commit and /.husky/pre-push to run tests before pushing code to github.

- Use PHP CS Fixer package to fix code style before commit with the same format for all developers.

- Use PHP stan package to check types, code logic, code quality and catch bugs automatically before commit.

- You could watch this youtube [video](https://youtu.be/rbqea2YZv6U) for reference. I will delete this video after the interview finished.
