## <p align="center">Laravel Api Case</p>
<p align="center">Emre BULUT</p>

<p align="center">
  <a href="https://skillicons.dev">
    <img src="https://skillicons.dev/icons?i=php,laravel,mysql" />
  </a>
</p>

Backend repository: https://github.com/iemrebulut/LaravelApiCase.git

## Kurulum Aşamaları

- Clone repository
- "composer install" komutunu çalıştırın
- .env.example dosyasını yeniden adlandırın veya .env olarak kopyalayın
- “php artisan key:generate” komutunu çalıştırın.
- .env dosyasında DB_DATABASE=laravel_api_case olarak düzenleyin
- MySQL serverını başlatın
- laravel_api_case adında bir veritabanı oluşturun
- "php artisan migrate" komutunu çalıştırın
- "php artisan db:seed" komutunu çalıştırın
- "php artisan queue:work" komutunu çalıştırın
- "php artisan serve" komutunu çalıştırın
- Postman uygulamasını açın ve install klasörü alında bulunan "LaravelApiCase.postman_collection.json" dosyasını import edin. (Port numaranız farklı ise url kısımlarını düzenleyin)
- Login requestini çalıştırın ve dönen result içerisindeki token datasını kopyalayın
- Create Order requestine giderek Authorization kısmından Type'ı Bearer Token olarak değiştirin. Gerekli alana kopyaladığınız token datasını yapıştırın. Create Order requestini çalıştırabilirsiniz.
- Request sonucu dönen sip_no'yu, Order Details requestinde url'nin sonuna yapıştırın. Aynı şekilde authorization kısmınden Type'ı Bearer Token olarak değiştirip gerekli alana token bilgisini yapıştırın. Order Details requestini çalıştırın. Sipariş bilgilerine ulaşabilirsiniz.

## ENDPOINTS

- (POST) login: Login işlemi için gerekli endpoint.
    - Body: {"email":"admin@admin.com", "password":"123456"}
- (POST) logout: Logout işlemi için gerekli endpoint. Authorization: Bearer Token gereklidir.
- (POST) order: Sipariş oluşturmak için gerekli endpoint. Birden fazla ürün ile işlem yapılabilir. "quantity" ile üründen kaç adet sipariş verildiği bildirilir.
    - Body: [{"product_id":3, "quantity":1},{"product_id":5,"quantity":1}]
- (GET) order/{sip_no}: Sipariş bilgilerini döndüren endpoint. Siparişteki ürünler, kampanyalar, sipariş tutarı bilgilerine ulaşılabilir. Bearer Token gereklidir.
