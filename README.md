# Laravel Docker Project

This repository contains a simple Laravel project setup using Docker.
The stack includes PHP 8.3, MySQL 8.0, redis, composer for a robust development environment.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Project Setup](#project-setup)
- [Running the Project](#running-the-project)
- [Connecting to MySQL](#connecting-to-mysql)
- [Troubleshooting](#troubleshooting)

## Prerequisites

Before you begin, ensure you have the following installed on your local machine:

- [Docker](https://www.docker.com/get-started)
- [Docker Compose](https://docs.docker.com/compose/install/)
- [Git](https://git-scm.com/)

You have needed the next ports available:

- 80
- 3306
- 9000
- 6379

## Project Setup

1. **Clone the Repository**

   ```bash
   git clone https://github.com/luis-knd/tempoAndJiraIntegrationAPI.git
   ```

2. **In the main path, up the containers**
    ```bash
    docker compose up --build -d
    ```

3. **Create Laravel Project**

    Go to src path:
    ```bash
    cd src
    ```
   And run the next command:
    ```bash
    docker-compose run --rm composer create-project laravel/laravel .
    ```

## Running the Project

1. **Run our Laravel application**

   Add to local hosts file the next line:
   ```apacheconf
   127.0.0.1 		tempo_and_jira_api
   ```
   In SO as Linux mint or Ubuntu, this file is on the path `/etc/hosts`

2. After that **Congratulation**, your app is in this [URL](http://127.0.0.1/)


## Connecting to MySQL
You can connect to the MySQL database using the following credentials:

```apacheconf
Host: lcandelario_db
Port: 3306
Database: example_db
Username: lcandelario
Password: lcandelario
```

## Troubleshooting

- **Rebuilding Containers**

  If you make changes to the Docker configuration, rebuild the containers:
   ```bash
   docker compose up --build --force-recreate --remove-orphans
   ```

- **Stopping Containers**

  Stop all running containers:
   ```bash
   docker-compose down
   ```