init:
	docker compose up -d --build
	docker compose exec php composer install
	docker compose exec php cp .env.example .env
	docker compose exec php php artisan key:generate
	docker compose exec php php artisan migrate
	docker compose exec php php artisan db:seed
	docker compose exec php php artisan storage:link

	docker compose exec php npm install
	docker compose exec php npm run build

start:
	docker compose start

stop:
	docker compose stop

attach:
	docker compose exec php bash