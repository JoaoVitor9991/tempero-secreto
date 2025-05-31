# Tempero Secreto

Sistema de gerenciamento de receitas com moderação de conteúdo.

## Requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Servidor web (Apache/Nginx)
- Extensões PHP:
  - PDO
  - PDO_MySQL
  - GD (para manipulação de imagens)

## Instalação

1. Clone o repositório:
```bash
git clone https://github.com/seu-usuario/tempero-secreto.git
cd tempero-secreto
```

2. Configure o banco de dados:
- Crie um banco de dados MySQL
- Importe o arquivo `database/schema.sql`
- Configure as credenciais em `config/database.php`

3. Configure o servidor web:
- Aponte o DocumentRoot para o diretório do projeto
- Certifique-se que o mod_rewrite está habilitado (Apache)
- Configure as permissões corretas para o diretório `uploads/`

4. Configure o arquivo `config/config.php`:
- Ajuste a URL do site
- Configure as credenciais de e-mail (opcional)

## Estrutura do Projeto

```
tempero-secreto/
├── assets/
│   ├── css/
│   └── js/
├── config/
├── database/
├── includes/
├── pages/
│   └── admin/
├── uploads/
└── index.php
```

## Funcionalidades

- Sistema de autenticação (usuário/admin)
- CRUD de receitas
- Moderação de receitas e comentários
- Categorias de receitas
- Upload de imagens
- Interface responsiva

## Segurança

- Proteção contra SQL Injection (PDO)
- Proteção CSRF
- Senhas criptografadas
- Validação de inputs
- Sanitização de outputs

## Contribuição

1. Faça um fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/nova-feature`)
3. Commit suas mudanças (`git commit -am 'Adiciona nova feature'`)
4. Push para a branch (`git push origin feature/nova-feature`)
5. Crie um Pull Request

## Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes. 