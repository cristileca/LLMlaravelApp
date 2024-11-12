- [Team](#team)
- [Tech Stack](#tech-stack)
- [Requirements](#requirements)
- [Credentials](#credentials)
- [Branching Model](#branching-model)
- [Pull Request Model](#pull-request-model)
- [Installation Guide](#installation-guide)
- [Deployment Guide](#deployment-guide)

---

# Team

- **Cristian Leca** (Dev)
- **Andrei Haba** (Dev)
- **Amalia Mareci** (Dev)
- **Robert Trasca** (Dev)
- **Valerian Psenicinii** (Dev)
- **Eduard Scripcarasu** (Team Lead)
- **Alexandru Beciu** (Project Manager)


# Tech Stack
- Languages (PHP / MySQL / CSS / HTML / JavaScript)
- Frameworks (Laravel / FilamentPhp / Livewire / TailwindCSS)
- Databases (MariaDB / Redis)

### Drivers

- **Files**
    - For **local** the files are stored locally
    - For **staging** and **production** the files are stored TBD
- **Mail**
    - For **local** the "Mailtrap" thrid-party service needs to be setup
    - For **staging** the "Mailtrap" thrid-party service is being used
    - For **production** TBD
- **Queue**
    - For **local** the "Sync" driver is being used (events are handled at request time)
    - For **staging** and **production** the "Redis" is being used (so "Supervisor" must be present on these servers)
- **Cache**
    - For **all** environments the "Redis" driver is being used
- **Session**
    - For **all** the "Cookie" driver is being used

# Requirements

> If you're using Docker then you can ignore this section for local development.   
> However, this might still help you on a staging or production server.

| Name     | Version |
|----------|---------|
| PHP      | `8.2.*` |
| MariaDB  | `10.x`  |
| Redis    | `6.x`   |
| GIT      | `2.x`   |
| Composer | `2.x`   |
| Node     | `18.x`  |
| NPM      | `6.x`   |

**PHP Extensions**

`bcmath`, `ctype`, `curl`, `fileinfo`, `gd`, `json`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `exif`, `zip`

# Credentials

#### URLs

- application root: [https://aprobare-facturi.test](http://aprobare-facturi.test)

#### Admin Login

- apb+admin@neurony.ro / admin (check 1Pass for stage)

####  Staging Server

The staging server is hosted on Forge.
You can find the credentials in 1Pass under "Aprobare Facturi Stage".

#### Production Server

TDB

# Branching Model

`master` - branch intended for production
`dev` - branch intended for development  
`feature/apb-{xxx}` - feature branch for development   
`bugfix/apb-{xxx}` - bugfix branch
`hotfix/apb-{xxx}` - hotfix branch

# Pull Request Model

For feature pull requests set `dev` as destination.

```
feature/apb-{xxx} -> dev
```

For bugfix pull requests set `dev` as destination.

```
bugfix/apb-{xxx} -> dev
```

# Installation Guide

**Important!**

You'll need to install [GIT](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git) on your machine in order to be able to clone the repository.

The application is configured to run on Docker, so you'll need to have [Docker Desktop](https://www.docker.com/products/docker-desktop) installed on your machine.

---

#### Clone Repository

Create the directory where to place the application

```
mkdir ~/code/aprobare-facturi
cd ~/code/aprobare-facturi
```

> This directory will have to be added in Docker Desktop from:   
> Preferences -> Resources -> File Sharing

Clone the Bitbucket repository

```
git clone git@github.com:neurony/aprobare-facturi.git .
```

#### Domain Setup

Add the domain to your hosts file

```
echo '127.0.0.1 aprobare-facturi.test' | sudo tee -a /etc/hosts
```

if on windows, open a notepad as administrator, and open the hosts file located at "C:\Windows\System32\drivers\etc", then add a new line:
127.0.0.1	aprobare-facturi.test

#### Spin Up Docker

Startup Docker Desktop and build the containers

```
docker-compose up --build -d
```

#### Database Setup

Mysql inside the "mariadb" Docker container

```
docker exec -ti aprobare_facturi_mariadb mysql -p

[no password]
```

Ensure the "aprobare_facturi" database exists

```
show databases;
```

If the database doesn't exist, create it

```
create database aprobare_facturi;
```

Exit the "mariadb" Docker container

```
exit;
```

#### Env File Setup

Bash inside the "php" Docker container

```
docker exec -ti aprobare_facturi_php bash
```

Generate an .env file based on the example

```
cp .env.example .env
```

> The `.env.example` file already contains correct connection details for mariadb, mailhog and redis.
>
> In some development cases you'll also need to complete the other env variables (please ask the team lead).

#### Application Setup

Bash inside the "php" Docker container

```
docker exec -ti aprobare_facturi_php bash
```

Install Composer dependencies

```
composer install
```

If composer install fails over and over again, follow below steps:
- delete vendor folder
- run outside docker but in the project folder: composer install --ignore-platform-reqs
- delete composer.lock
- run composer install again from inside the docker (docker exec -ti aprobare_facturi_php bash)docker exec -ti aprobare_facturi_php bash

Install Node dependencies

```
npm install
```

Generate an application key

```
php artisan key:generate
```

Migrate the database

```
php artisan migrate
```

Seed the database

```
php artisan db:seed
```

Seed the dummy data *(optionally if you want the data)*

```
php artisan db:seed --class=DummySeeder
```

# Deployment Guide

The deployment model is as follows:

```
dev -> master
```

Each deployment is done via Envoyer automatically, by merging a PR into the dev branch
Deployments for production TBD

You can access the admin panel through https://aprobare-facturi.test/admin
