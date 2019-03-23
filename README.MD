
# Viloveul Content Management System (Rest API)

need php 7, openssl

```shell
composer create-project viloveul-cms/api your-project-name
cd your-project-name
```

Edit file .env anda sesuai kebutuhan :

| KEY | VALUE | KETERANGAN |
| - | - | - |
| VILOVEUL_BASEPATH | string | sebagai prefix url (default = /) |
| VILOVEUL_UPLOAD_TARGET | string | directory upload (default = /public/uploads) |
| VILOVEUL_AUTH_NAME | string | Header name untuk basic auth (default = Bearer) |
| VILOVEUL_AUTH_PRIVATE_KEY | string | path ke file private key |
| VILOVEUL_AUTH_PUBLIC_KEY | string | path ke file public key |
| VILOVEUL_AUTH_PASSPHRASE | string | phrase untuk encrypt (require) |
| VILOVEUL_DB_DRIVER | string | @see eloquent orm |
| VILOVEUL_DB_HOST | string | @see eloquent orm |
| VILOVEUL_DB_PORT | string | @see eloquent orm |
| VILOVEUL_DB_NAME | string | @see eloquent orm |
| VILOVEUL_DB_USERNAME | string | @see eloquent orm |
| VILOVEUL_DB_PASSWD | string | @see eloquent orm |
| VILOVEUL_DB_PREFIX | string | @see eloquent orm |
| VILOVEUL_DB_CHARSET | string | @see eloquent orm |
| VILOVEUL_DB_COLLATION | string | @see eloquent orm |
| VILOVEUL_CACHE_ADAPTER | string | Adapter untuk cache (default = redis, opsi = redis or apcu) |
| VILOVEUL_CACHE_LIFETIME | number | waktu caching |
| VILOVEUL_CACHE_HOST | string | available for redis adapter |
| VILOVEUL_CACHE_PORT | number | available for redis adapter |

setelah menyesuaikan konfigurasi .env anda, kemudian lakukan perintah sebagai berikut untuk memulai instalasi
```shell
php sbin/viloveulc cms:install
```
setelah selesai proses instalasi, jalankan perintah berikut:
```shell
php -S localhost:19911 -t public
```


## UNDER DOCKER COMPOSE
simple, just run
```shell
docker run -e VILOVEUL_AUTH_PASSPHRASE=dordor -e ADMIN_EMAIL=your@mail.com -e ADMIN_PASSWORD=YourP@s5w0rdH3rE -p 19911:19911
```

your local ready : http://localhost:19911