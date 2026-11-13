\getenv app_password APP_DB_PASSWORD

SELECT format('CREATE ROLE %I LOGIN', :'db_user')
WHERE NOT EXISTS (
    SELECT 1
    FROM pg_roles
    WHERE rolname = :'db_user'
)
\gexec

ALTER ROLE :"db_user" WITH LOGIN PASSWORD :'app_password';
GRANT CONNECT ON DATABASE :"db_name" TO :"db_user";
GRANT ALL ON SCHEMA public TO :"db_user";
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO :"db_user";
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO :"db_user";
ALTER DEFAULT PRIVILEGES FOR ROLE :"db_admin_user" IN SCHEMA public
    GRANT ALL ON TABLES TO :"db_user";
ALTER DEFAULT PRIVILEGES FOR ROLE :"db_admin_user" IN SCHEMA public
    GRANT ALL ON SEQUENCES TO :"db_user";
