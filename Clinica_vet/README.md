# Clínica Veterinária - Sistema de Gerenciamento

<div align="center">

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-005C84?style=for-the-badge&logo=mysql&logoColor=white)
![Status](https://img.shields.io/badge/Status-Ativo-brightgreen?style=for-the-badge)

Um sistema web robusto para gerenciamento completo de clínicas veterinárias, desenvolvido em **PHP puro** com **MySQL**, oferecendo controle de acesso por papéis e funcionalidades essenciais para otimizar o fluxo de trabalho.

[Características](#características) • [Tecnologias](#tecnologias) • [Instalação](#instalação) • [Como Usar](#como-usar) • [Arquitetura](#arquitetura)

</div>

---

## 📋 Visão Geral

O **Clínica Veterinária - Sistema de Gerenciamento** é uma solução completa para administração de clínicas veterinárias. O sistema foi desenvolvido com foco em facilitar o controle de agendamentos, consultas e gerenciamento administrativo, oferecendo interfaces específicas para diferentes perfis de usuários.

### Perfis de Usuários

- **👨‍💼 Gerente**: Acesso total ao sistema, relatórios, configurações e gestão de usuários
- **👨‍⚕️ Médico Veterinário**: Gerenciamento de consultas, pacientes e histórico clínico
- **👩‍💼 Recepcionista**: Agendamento de consultas, gestão de clientes e acompanhamento de atendimentos

---

## ✨ Características

### 🗓️ Gerenciamento de Agendamentos
- Agendamento intuitivo de consultas
- Visualização de calendário
- Notificações de conflitos de horário
- Histórico de consultas

### 👥 Gestão de Clientes e Pacientes
- Cadastro completo de clientes (tutores)
- Registro detalhado de animais/pacientes
- Armazenamento de dados de contato e endereço
- Histórico de atendimentos por paciente

### 📊 Controle Administrativo
- Dashboard com estatísticas gerais
- Relatórios de atendimentos
- Gerenciamento de usuários com controle de permissões
- Auditoria de ações no sistema

### 🔐 Segurança
- Autenticação de usuários
- Controle de acesso baseado em papéis (RBAC)
- Proteção de dados sensíveis
- Sessões seguras

---

## 🛠️ Tecnologias

| Tecnologia | Versão | Descrição |
|-----------|--------|-----------|
| **PHP** | 7.0+ | Linguagem backend |
| **MySQL** | 5.7+ | Banco de dados relacional |
| **HTML5** | - | Marcação de estrutura |
| **CSS3** | - | Estilização |
| **JavaScript** | ES5+ | Interatividade frontend |

### Arquitetura
- **Padrão**: MVC (Model-View-Controller)
- **Abordagem**: PHP Procedural/OOP
- **Banco de Dados**: MySQL com normalização

---

## 📦 Instalação

### Pré-requisitos
- PHP 7.0 ou superior
- MySQL 5.7 ou superior
- Servidor web (Apache/Nginx)
- Composer (opcional, para gerenciar dependências)

### Passo 1: Clone o Repositório
```bash
git clone https://github.com/LucasNascente/Clinica_Vet_SyS.git
cd Clinica_Vet_SyS
```

### Passo 2: Configure o Banco de Dados
```bash
# Acesse seu cliente MySQL
mysql -u seu_usuario -p

# Crie o banco de dados
CREATE DATABASE clinica_vet;
USE clinica_vet;

# Importe o arquivo de banco de dados
SOURCE Clinica_vet/database/schema.sql;
```

### Passo 3: Configure as Credenciais
Edite o arquivo de configuração (`config.php` ou similar):
```php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
define('DB_NAME', 'clinica_vet');
?>
```

### Passo 4: Configure o Servidor Web
- Coloque os arquivos no diretório raiz do servidor web
- Se usando Apache, certifique-se de que `mod_rewrite` está ativado
- Acesse `http://localhost/Clinica_Vet_SyS`

### Passo 5: Login Inicial
```
Usuário: admin
Senha: admin123
```
⚠️ **Altere a senha padrão imediatamente após o primeiro login!**

---

## 🚀 Como Usar

### Para a Recepção
1. Faça login com suas credenciais
2. Acesse "Agendamentos" → "Novo Agendamento"
3. Selecione o cliente (ou crie um novo)
4. Escolha o paciente e horário disponível
5. Confirme o agendamento

### Para o Médico Veterinário
1. Acesse "Consultas" para visualizar seus agendamentos
2. Selecione a consulta para registrar:
   - Anamnese
   - Exame físico
   - Diagnóstico
   - Prescrição e orientações
3. Finalize a consulta com observações

### Para o Gerente
1. Acesse o Dashboard para visualizar métricas
2. Gere relatórios em "Relatórios" → "Atendimentos"
3. Gerencie usuários em "Configurações" → "Usuários"
4. Monitore a saúde do sistema

---

## 📁 Estrutura de Pastas

```
Clinica_Vet_SyS/
├── Clinica_vet/
│   ├── config/
│   │   └── database.php          # Configurações do banco de dados
│   ├── classes/
│   │   ├── Database.php          # Classe de conexão com BD
│   │   ├── User.php              # Classe de usuário
│   │   └── ...
│   ├── views/
│   │   ├── index.php             # Página inicial
│   │   ├── login.php             # Login
│   │   ├── dashboard/
│   │   ├── agendamentos/
│   │   └── ...
│   ├── controllers/
│   │   ├── UserController.php
│   │   ├── AppointmentController.php
│   │   └── ...
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   ├── database/
│   │   └── schema.sql            # Script SQL do banco
│   └── index.php                 # Ponto de entrada
└── README.md
```

---

## 🔄 Fluxo de Funcionamento

```
Usuário Acessa
    ↓
Login (Autenticação)
    ↓
Validação de Permissões (RBAC)
    ↓
Dashboard/Menu Principal
    ↓
Ação Específica (Agendar, Consultar, Relatar)
    ↓
Operação no Banco de Dados
    ↓
Resultado Exibido na View
```

---

## 🔒 Segurança

O sistema implementa as seguintes medidas de segurança:

- ✅ **Autenticação**: Validação de credenciais
- ✅ **Autorização**: Controle de acesso por papel
- ✅ **Proteção de Sessão**: Uso de cookies seguros
- ✅ **Validação de Entrada**: Prevenção de SQL Injection
- ✅ **Criptografia**: Armazenamento seguro de senhas
- ⚠️ **HTTPS**: Recomenda-se usar em produção

---

## 📈 Melhorias Futuras

- [ ] API REST para integração com aplicações mobile
- [ ] Dashboard com gráficos mais interativos
- [ ] Notificações por email/SMS para clientes
- [ ] Sistema de pagamento integrado
- [ ] Backup automático de dados
- [ ] Melhor responsividade mobile
- [ ] Histórico de medicamentos e vacinas
- [ ] Sistema de inventário/estoque

---

## 🤝 Como Contribuir

Contribuições são bem-vindas! Para contribuir:

1. Faça um Fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

---

## 📝 Licença

Este projeto está sob a licença MIT. Veja o arquivo `LICENSE` para mais detalhes.

---

## 📧 Contato

**Desenvolvedor**: Lucas Nascente  
**Email**: [seu_email@exemplo.com]  
**GitHub**: [@LucasNascente](https://github.com/LucasNascente)  
**LinkedIn**: [Seu Perfil LinkedIn]

---

## 🙏 Agradecimentos

- Comunidade PHP
- Comunidade de Desenvolvimento Web
- Todos que contribuem com feedback e sugestões

---

## 📚 Referências e Recursos

- [Documentação PHP](https://www.php.net/docs.php)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [OWASP - Segurança Web](https://owasp.org/)
- [Padrão MVC](https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller)

---

<div align="center">

**⭐ Se este projeto foi útil, considere deixar uma estrela!**

Desenvolvido com ❤️ por Lucas Nascente

</div>
