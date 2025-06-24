install:
	composer install

clean:
	rm -rf vendor
    php bin/console cache:clear