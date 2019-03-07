
# Viloveul Content Management System (Rest API)

need php 7, openssl

```bash
composer create-project viloveul-cms/api your-project-name
cd your-project-name
```

Edit file .env anda sesuai kebutuhan :

| KEY | VALUE | KETERANGAN |
| - | - | - |
| BASEPATH | string | sebagai prefix url (default = /api/v1) |
| UPLOAD_TARGET | string | directory upload (default = /public/uploads) |
| AUTH_NAME | string | Header name untuk basic auth (default = Bearer) |
| AUTH_PRIVATE_KEY | string | path ke file private key |
| AUTH_PUBLIC_KEY | string | path ke file public key |
| AUTH_PASSPHRASE | string | phrase untuk encrypt (require) |
| DB_DRIVER | string | @see eloquent orm |
| DB_HOST | string | @see eloquent orm |
| DB_PORT | string | @see eloquent orm |
| DB_NAME | string | @see eloquent orm |
| DB_USERNAME | string | @see eloquent orm |
| DB_PASSWD | string | @see eloquent orm |
| DB_PREFIX | string | @see eloquent orm |
| DB_CHARSET | string | @see eloquent orm |
| DB_COLLATION | string | @see eloquent orm |
| CACHE_ADAPTER | string | Adapter untuk cache (default = redis, opsi = redis or apcu) |
| CACHE_LIFETIME | number | waktu caching |
| CACHE_HOST | string | available for redis adapter |
| CACHE_PORT | number | available for redis adapter |

setelah menyesuaikan konfigurasi .env anda, kemudian lakukan perintah sebagai berikut untuk memulai instalasi
```bash
php viloveulc install
```
setelah selesai proses instalasi, jalankan perintah berikut:
```bash
php -S localhost:3333 -t public
```
response rest api based on : https://jsonapi.org/recommendations


## UNDER DOCKER COMPOSE
simple, just run
```bash
git clone -b v1 --single-branch https://github.com/zafex/viloveul-cms-api.git
cd viloveul-cms-api
docker-compose up -d
docker exec -ti viloveul-cms-api composer bootstrap
docker exec -ti viloveul-cms-api php viloveulc install
```

your local ready : http://localhost:9999/api/v1