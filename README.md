# Multi-Gateway Payment System

API RESTful para gerenciamento de pagamentos multi-gateway construída com Laravel 11, implementando o nível 3 do desafio com autenticação, roles, TDD e Docker.

## 📋 Requisitos

- Docker e Docker Compose
- PHP 8.2+ (para desenvolvimento local)
- Composer (para desenvolvimento local)

## 🚀 Instalação e Execução

### Com Docker (Recomendado)

1. **Clone o repositório**
   ```bash
   git clone <repository-url>
   cd teste-pratico-BeTalent
   ```

2. **Inicie os containers**
   ```bash
   docker-compose up -d
   ```

3. **Configure o ambiente**
   ```bash
   docker-compose exec app cp .env.example .env
   docker-compose exec app php artisan key:generate
   ```

4. **Execute as migrations e seeders**
   ```bash
   docker-compose exec app php artisan migrate:fresh --seed --force
   ```

5. **Acesse a API**
   - API: http://localhost:8080
   - Gateway 1: http://localhost:3001
   - Gateway 2: http://localhost:3002

### 📦 Postman Collection

Importe o arquivo `postman-collection.json` no Postman para testar todas as funcionalidades da API com exemplos prontos para uso.

### Desenvolvimento Local

1. **Instale as dependências**
   ```bash
   composer install
   ```

2. **Configure o ambiente**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Configure o banco de dados MySQL**
   - Crie um banco de dados `payment_db`
   - Configure as credenciais no `.env`

4. **Execute as migrations e seeders**
   ```bash
   php artisan migrate:fresh --seed
   ```

5. **Inicie o servidor**
   ```bash
   php artisan serve
   ```

## 🏗️ Arquitetura

### Sistema de Roles

- **ADMIN**: Acesso total ao sistema
- **MANAGER**: Pode gerenciar produtos e usuários
- **FINANCE**: Pode gerenciar produtos e realizar reembolsos
- **USER**: Acesso básico ao sistema

### Multi-Gateway

O sistema implementa o padrão Strategy para facilitar a adição de novos gateways:

- **Gateway 1**: Autenticação via Bearer Token
- **Gateway 2**: Autenticação via Headers personalizados
- **Fallback automático**: Tenta o próximo gateway em caso de falha

## 📚 Rotas da API

### Rotas Públicas

#### Autenticação
- `POST /api/login` - Realizar login
- `POST /api/purchase` - Realizar compra

### Rotas Protegidas (requerem autenticação)

#### Autenticação
- `POST /api/logout` - Logout
- `GET /api/me` - Informações do usuário autenticado

#### Transações
- `GET /api/transactions` - Listar transações
- `GET /api/transactions/{id}` - Detalhes da transação
- `POST /api/transactions/{id}/refund` - Reembolsar transação (FINANCE/ADMIN)

#### Clientes
- `GET /api/clients` - Listar clientes
- `GET /api/clients/{id}` - Detalhes do cliente e compras

#### Produtos
- `GET /api/products` - Listar produtos
- `GET /api/products/{id}` - Detalhes do produto
- `POST /api/products` - Criar produto (MANAGER/ADMIN)
- `PUT /api/products/{id}` - Atualizar produto (MANAGER/ADMIN)
- `DELETE /api/products/{id}` - Excluir produto (MANAGER/ADMIN)

#### Usuários (ADMIN apenas)
- `GET /api/users` - Listar usuários
- `POST /api/users` - Criar usuário
- `GET /api/users/{id}` - Detalhes do usuário
- `PUT /api/users/{id}` - Atualizar usuário
- `DELETE /api/users/{id}` - Excluir usuário

#### Gateways (ADMIN apenas)
- `GET /api/gateways` - Listar gateways
- `GET /api/gateways/{id}` - Detalhes do gateway
- `PUT /api/gateways/{id}/status` - Ativar/desativar gateway
- `PUT /api/gateways/{id}/priority` - Alterar prioridade do gateway

## 🔐 Usuários Padrão

Após executar os seeders, os seguintes usuários estarão disponíveis:

| Email | Senha | Role |
|-------|-------|------|
| admin@payment.com | password | ADMIN |
| manager@payment.com | password | MANAGER |
| finance@payment.com | password | FINANCE |
| user@payment.com | password | USER |

## 📝 Exemplos de Uso

### Login
```bash
curl -X POST http://localhost:8080/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@payment.com",
    "password": "password"
  }'
```

### Realizar Compra
```bash
curl -X POST http://localhost:8080/api/purchase \
  -H "Content-Type: application/json" \
  -d '{
    "name": "João Silva",
    "email": "joao@example.com",
    "card_number": "5569000000006063",
    "cvv": "010",
    "products": [
      {"product_id": 1, "quantity": 2},
      {"product_id": 2, "quantity": 1}
    ]
  }'
```

### Listar Transações
```bash
curl -X GET http://localhost:8080/api/transactions \
  -H "Authorization: Bearer SEU_TOKEN"
```

## 🧪 Testes

### Executar todos os testes
```bash
docker-compose exec app php artisan test
```

### Executar testes específicos
```bash
docker-compose exec app php artisan test --filter TransactionTest
```

### Executar testes com coverage
```bash
docker-compose exec app php artisan test --coverage
```

## 📊 Estrutura do Banco de Dados

### Tabelas Principais
- **users**: Usuários do sistema com roles
- **gateways**: Configurações dos gateways de pagamento
- **clients**: Clientes que realizam compras
- **products**: Produtos disponíveis para venda
- **transactions**: Transações de pagamento
- **transaction_products**: Relacionamento entre transações e produtos

## 🔧 Configuração dos Gateways

### Gateway 1
- **URL**: http://localhost:3001
- **Autenticação**: Bearer Token via login
- **Endpoint Login**: POST /login
- **Endpoint Transação**: POST /transactions

### Gateway 2
- **URL**: http://localhost:3002
- **Autenticação**: Headers personalizados
- **Endpoint Transação**: POST /transacoes

## 🐛 Troubleshooting

### Problemas Comuns

1. **Portas em uso**: Verifique se as portas 8080, 3001, 3002 e 3306 estão disponíveis
2. **Permissões**: No Windows, pode ser necessário executar o PowerShell como administrador
3. **Conexão com banco**: Aguarde alguns segundos após iniciar os containers para o MySQL estar pronto

### Logs
- **Logs da aplicação**: `docker-compose logs app`
- **Logs do MySQL**: `docker-compose logs mysql`
- **Logs dos gateways**: `docker-compose logs gateway1 gateway2`

## 📈 Monitoramento

### Health Check
- GET http://localhost:8080/up

### Informações do Sistema
A API responde com informações detalhadas sobre transações, incluindo:
- Gateway utilizado
- Status da transação
- Produtos comprados
- Dados do cliente

## 🔄 Fluxo de Pagamento

1. **Requisição**: Cliente envia dados da compra
2. **Validação**: Sistema valida produtos e calcula total
3. **Processamento**: Tenta Gateway 1 (prioridade 1)
4. **Fallback**: Se falhar, tenta Gateway 2 (prioridade 2)
5. **Resposta**: Retorna sucesso se algum gateway aprovar
6. **Persistência**: Salva transação com detalhes do gateway

## 🛡️ Segurança

- Tokens JWT via Laravel Sanctum
- Middleware de verificação de roles
- Validação de dados em todos os endpoints
- Senhas hasheadas com bcrypt
- Proteção contra mass assignment

## 📝 Licença

MIT License - Veja o arquivo LICENSE para detalhes.