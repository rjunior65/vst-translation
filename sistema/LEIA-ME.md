# VST Ponto — publicação e piloto

Sistema de ponto com GPS, histórico de horas, chat e app para celular, em `vst-translation.com/sistema`.
Usa o mesmo banco de dados do site (tabelas com prefixo `pt_`) e a mesma configuração (`includes/config.php`).

## 1. Enviar os arquivos (cPanel → Gerenciador de Arquivos)

Envie para `public_html`:

| O quê | Onde |
|---|---|
| a pasta `sistema/` inteira | `public_html/sistema/` |
| `index.html`, `css/style.css`, `js/script.js` | link "Portal" no menu do site |
| `api/apply.php` | correção do envio de currículos |

**Não envie** o `includes/config.php` do repositório: o do servidor é o que tem a senha real do banco.
Nunca envie `includes/config.local.php` (é só para testes no computador).

## 2. Instalar (uma vez)

1. Abra `https://vst-translation.com/sistema/instalar.php`.
2. Informe a senha do banco (a mesma do `includes/config.php` do servidor), o nome do administrador e uma senha.
3. O instalador cria as tabelas e o primeiro administrador, e depois se bloqueia.

Em versões futuras: entre como admin e abra `instalar.php` → **Atualizar estrutura do banco** (não apaga nada).

## 3. Configurar no painel do admin

1. **Usuários → Importar equipe**: cria o acesso de quem está na equipe do site (usuário `nome.sobrenome`, senha temporária `vst123456`).
2. **Usuários → Novo usuário**: crie os CEOs e quem não está no site.
3. **Locais**: cadastre o escritório (estando lá, use "Usar minha localização atual") e o raio.
4. **Jornadas**: crie a jornada (ex.: 08:00–17:00, 60 min de intervalo) e use "Aplicar a quem não tem".
5. Em cada usuário, ajuste o **modo de trabalho**: Escritório, Externo (motorista, eletricista) ou Flexível (CEOs, patrocinador, consultoria).

## 4. Lembretes de ponto (tarefa agendada)

cPanel → **Cron Jobs** → a cada 15 minutos (`*/15 * * * *`), comando:

```
php /home/yasmi374/public_html/sistema/cron/lembretes.php
```

Avisa no celular quem ainda não registrou a entrada e quem esqueceu a saída (uma vez por dia cada).
Pode ser desligado em **Jornadas → Lembretes de ponto no celular**.

## 5. Instalar no celular (cada funcionário)

- **Android (Chrome):** abrir `vst-translation.com/sistema`, entrar e tocar em **Instalar** no cartão que aparece (ou menu ⋮ → "Instalar app").
- **iPhone (Safari):** abrir o endereço, tocar em **Compartilhar (□↑) → Adicionar à Tela de Início** e abrir pelo ícone.
- Depois, em **Meu ponto → Notificações → Ativar** e tocar em **Enviar teste**.
  No iPhone, as notificações só funcionam com o app aberto pelo ícone da tela inicial (iOS 16.4 ou mais novo).
- Permitir a **localização** quando o celular pedir.

## 6. Piloto (1 semana, 2 ou 3 pessoas)

Sugestão: 1 pessoa do escritório, a motorista (modo Externo) e 1 administrador.

Conferir no fim de cada dia:
- [ ] Os pontos aparecem em **Hoje** e em **Pontos**, no mapa, perto do escritório.
- [ ] Distância e precisão do GPS fazem sentido (sem "fora do local" indevido).
- [ ] As horas em **Relatórios** batem com o que a pessoa trabalhou.
- [ ] Mensagens e anexos chegam, e a notificação aparece com o app fechado.
- [ ] Lembretes chegam no horário (entrada após a tolerância, saída 30 min após o fim).

Ao final: ajustar raio, tolerâncias e jornadas, e liberar para todos com **Importar equipe**.

## Onde ficam as coisas

- Anexos do chat: `/home/yasmi374/vst-private/chat/` (fora do site, como os currículos).
- Toda alteração do admin fica registrada na tabela `pt_audit_log`, com o motivo.
