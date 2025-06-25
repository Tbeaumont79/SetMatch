install:
	composer install
	symfony console doctrine:migrations:migrate

clean:
	rm -rf vendor
    php bin/console cache:clear