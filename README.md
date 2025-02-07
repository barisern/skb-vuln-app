# SKB Vuln App

Just a simple vuln app :)

## Prerequisites

- Docker
- Docker Compose

## Quick Start

1. Clone the repository
```bash
git clone https://github.com/barisern/skb-vuln-app.git
cd skb-vuln-app
```


2. Start the application using Docker Compose
```bash
docker-compose up -d
```

3. Add the following line to your hosts file (C:\Windows\System32\drivers\etc\hosts):
```
127.0.0.1 skb
```

4. Access the application
- Main application: http://skb:80
- MongoDB (docker internal): mongodb:27017

