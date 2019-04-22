
#  Viloveul Content Management System (Rest API)

PHP7, OpenSSL, php-apcu/php-redis
```shell
composer create-project viloveul-cms/api your-project-name
cd your-project-name
```
Edit file .env anda sesuai kebutuhan :

| KEY | VALUE | KETERANGAN |
| - | - | - |
| VILOVEUL_AUTH_NAME | string | Header name untuk basic auth (default = Bearer) |
| VILOVEUL_AUTH_PRIVATE_KEY | string | path ke file private key |
| VILOVEUL_AUTH_PUBLIC_KEY | string | path ke file public key |
| VILOVEUL_AUTH_PASSPHRASE | string | phrase untuk encrypt (require) |
| VILOVEUL_DB_DRIVER | string | |
| VILOVEUL_DB_HOST | string | |
| VILOVEUL_DB_PORT | string | |
| VILOVEUL_DB_NAME | string | |
| VILOVEUL_DB_USERNAME | string | |
| VILOVEUL_DB_PASSWD | string | |
| VILOVEUL_DB_PREFIX | string | |
| VILOVEUL_DB_CHARSET | string | |
| VILOVEUL_DB_COLLATION | string | |
| VILOVEUL_CACHE_ADAPTER | string | Adapter untuk cache (default = redis, opsi = redis or apcu) |
| VILOVEUL_CACHE_LIFETIME | number | waktu caching |
| VILOVEUL_CACHE_HOST | string | available for redis adapter |
| VILOVEUL_CACHE_PORT | number | available for redis adapter |

anda dapat menyesuaikan konfigurasi .env anda dengan cara menjalankan perintah berikut
```shell
sbin/viloveulc cms:envi
```
kemudian memulai tahap instalasi dengan perintah :
```shell
sbin/viloveulc cms:install
```
membuat user "Administrator":
```shell
sbin/viloveulc cms:admin
```
Dummy content (Optional):
```shell
sbin/viloveulc cms:dummy
```
setelah selesai proses instalasi, jalankan perintah berikut:
```shell
php -S localhost:19911 -t public
```

##  UNDER DOCKER
simple, just run
```shell
docker run -p 19911:19911 -e VILOVEUL_AUTH_PASSPHRASE=dordor -e ADMIN_EMAIL=your@mail.com -e ADMIN_PASSWORD=YourP@s5w0rdH3rE -e VILOVEUL_DB_HOST=YOUR_DB_HOST fajrulaz/viloveul-cms-api
```
your local ready : http://localhost:19911

* Note: ini adalah headless CMS, bukan Stack CMS

##  FEATURES

| MODULE | ENDPOINT | KETERANGAN |
| - | - | - |
| AUTH | <code>POST /auth/login</code> | authentication user yang telah terdaftar |
| | <code>POST /auth/register</code> | register user / create account |
| | <code>POST /auth/forgot</code> | request password sementara |
| USER | <code>POST /user/create</code> | create new user account |
| | <code>GET /user/index</code> | get all registered user |
| | <code>GET /user/detail/{:id}</code> | get detail user |
| | <code>GET /user/me</code> | get current user |
| | <code>POST /user/update/{:id}</code> | update user account |
| | <code>POST /user/relations/{:id}</code> | set user role relations |
| | <code>DELETE /user/delete/{:id}</code> | delete registered user (only flag) |
| PROFILE | <code>GET /profile/detail/{:id}</code> | get profile user |
| | <code>POST /profile/detail/{:id}</code> | set profile user |
| ROLE | <code>POST /role/create</code> | create new role user |
| | <code>GET /role/index</code> | get all created roles |
| | <code>GET /role/detail/{:id}</code> | get detail role |
| | <code>POST /role/assign/{:id}</code> | Assign some role to another role (parent-child) |
| | <code>POST /role/unassign/{:id}</code> | delete assigned role from other role |
| | <code>POST /role/update/{:id}</code> | update role |
| | <code>DELETE /role/delete/{:id}</code> | delete role |
| TAG atau KATEGORI | <code>POST /tag/create</code> | Create new tag |
| | <code>GET /tag/index</code> | Get all tag |
| | <code>GET /tag/detail/{:id}</code> | Get detail tag |
| | <code>POST /tag/update/{:id}</code> | Update tag |
| | <code>DELETE /tag/delete/{:id}</code> | Delete tag |
| POST atau KONTEN | <code>POST /post/create</code> | Create new post/content |
| | <code>GET /post/index</code> | Get all post/content |
| | <code>GET /post/detail/{:id}</code> | Get detail post/content |
| | <code>POST /post/update/{:id}</code> | Update post/content |
| | <code>DELETE /post/delete/{:id}</code> | DELETE post/content |
| MEDIA atau GALERI | <code>POST /media/upload</code> | UPLOAD FILE(S) |
| | <code>GET /media/index</code> | Get uploaded FILES |
| | <code>GET /media/detail/{:id}</code> | Get detail media |
| | <code>DELETE /media/delete/{:id}</code> | delete media |
