# Conecta Corrente

Aplicação PHP para divulgar estabelecimentos e eventos de Corrente.

## Configuração local

1. Use PHP com Apache e MySQL/MariaDB.
2. Crie o banco e as tabelas importando `database.sql`.
3. Configure a conexão do banco em `conexao.php`.
4. Configure `ADMIN_USER` e `ADMIN_PASSWORD` no ambiente do Apache e reinicie o servidor. No Apache, por exemplo:

   ```apache
   SetEnv ADMIN_USER admin
   SetEnv ADMIN_PASSWORD substitua-por-uma-senha-forte
   ```

   Use uma senha forte e não a inclua no repositório.
5. Acesse `index.php` pelo servidor local.
