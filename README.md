# Remember Me LINE Bot

Remember Me will remember your To-Do List and Notes so that you never forget them. This repository was grabbed from my class assignment at Dicoding Indonesia: Learn to build LINE Chatbot ([Belajar Membangun LINE Chatbot](https://www.dicoding.com/academies/32)). I've enrolled in this class as a part of [LINE](https://line.me) Developer Academy 2019.

This bot can save notes from different source like Personal Chat, Multi Chat, and Group Chat. So you can make your Personal To-Do List or To-Do List for team.

### Official Account Sample

Bot ID: [@343bjvaa](https://page.line.me/343bjvaa)

## Screenshot

<img src="./screenshots/personal%20chat.jpg" width="512" title="Personal Chat">

### App Features

- [x] Saving notes
- [x] Database notes for personal, room, and group are seperated
- [x] Greeting message
- [x] Instructions for use
- [x] Auto response
- [x] Fallback message

### Configurations

Env properties:

- `DATABASE_URL`: Database (PostgreSQL by default, update config/database.php to use different database) url connection
- `CHANNEL_ACCESS_TOKEN`: From [LINE Developers](https://developers.line.biz/console)
- `CHANNEL_SECRET`: From [LINE Developers](https://developers.line.biz/console)

And set Webhook URL at [LINE Developers](https://developers.line.biz/console) with your webhook endpoint.
Example: `https://www.example.com/public/webhook`

### Built With

- [Lumen](https://lumen.laravel.com/docs/8.x)
- [LINE Messaging API](https://developers.line.biz/en/docs/messaging-api/)

## Author

- **Fahmi Al**
