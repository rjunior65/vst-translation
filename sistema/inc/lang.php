<?php
/* ============================================================
   VST Ponto — translations (PT / EN / ES, like the website).
   ?lang=xx switches and remembers the choice in a cookie.
   ============================================================ */

function current_lang(): string {
    static $lang = null;
    if ($lang !== null) return $lang;
    $ok = ['pt', 'en', 'es'];
    $q  = $_GET['lang'] ?? '';
    if (in_array($q, $ok, true)) {
        setcookie('vst_ponto_lang', $q, ['expires' => time() + 31536000, 'path' => '/', 'samesite' => 'Lax']);
        return $lang = $q;
    }
    $c = $_COOKIE['vst_ponto_lang'] ?? '';
    if (in_array($c, $ok, true)) return $lang = $c;
    $al = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 0, 2));
    return $lang = in_array($al, $ok, true) ? $al : 'en';
}

function t(string $key, array $vars = []): string {
    static $dict = null;
    if ($dict === null) $dict = pt_dictionary();
    $s = $dict[$key][current_lang()] ?? $dict[$key]['en'] ?? $key;
    foreach ($vars as $k => $v) $s = str_replace('{' . $k . '}', (string)$v, $s);
    return $s;
}

function pt_dictionary(): array {
    return [
        // general
        'app_name'        => ['pt' => 'VST Ponto', 'en' => 'VST Time Clock', 'es' => 'VST Control Horario'],
        'save'            => ['pt' => 'Salvar', 'en' => 'Save', 'es' => 'Guardar'],
        'cancel'          => ['pt' => 'Cancelar', 'en' => 'Cancel', 'es' => 'Cancelar'],
        'back'            => ['pt' => 'Voltar', 'en' => 'Back', 'es' => 'Volver'],
        'logout'          => ['pt' => 'Sair', 'en' => 'Log out', 'es' => 'Salir'],
        'home'            => ['pt' => 'Início', 'en' => 'Home', 'es' => 'Inicio'],
        'my_password'     => ['pt' => 'Minha senha', 'en' => 'My password', 'es' => 'Mi contraseña'],
        'website'         => ['pt' => 'Site VST', 'en' => 'VST website', 'es' => 'Sitio VST'],
        'err_csrf'        => ['pt' => 'Token de segurança inválido. Volte e tente novamente.',
                              'en' => 'Invalid or missing security token. Go back and try again.',
                              'es' => 'Token de seguridad inválido. Vuelva e inténtelo de nuevo.'],
        'yes'             => ['pt' => 'Sim', 'en' => 'Yes', 'es' => 'Sí'],
        'no'              => ['pt' => 'Não', 'en' => 'No', 'es' => 'No'],

        // login
        'login_title'     => ['pt' => 'Entrar', 'en' => 'Sign in', 'es' => 'Iniciar sesión'],
        'username'        => ['pt' => 'Usuário', 'en' => 'Username', 'es' => 'Usuario'],
        'password'        => ['pt' => 'Senha', 'en' => 'Password', 'es' => 'Contraseña'],
        'login_btn'       => ['pt' => 'Entrar', 'en' => 'Sign in', 'es' => 'Entrar'],
        'login_hint'      => ['pt' => 'Seu usuário é nome.sobrenome (ex.: fayan.abrams).',
                              'en' => 'Your username is firstname.lastname (e.g. fayan.abrams).',
                              'es' => 'Su usuario es nombre.apellido (ej.: fayan.abrams).'],
        'login_wrong'     => ['pt' => 'Usuário ou senha incorretos.', 'en' => 'Incorrect username or password.',
                              'es' => 'Usuario o contraseña incorrectos.'],
        'login_locked'    => ['pt' => 'Muitas tentativas. Aguarde {min} minutos e tente novamente.',
                              'en' => 'Too many attempts. Please wait {min} minutes and try again.',
                              'es' => 'Demasiados intentos. Espere {min} minutos e inténtelo de nuevo.'],
        'login_forgot'    => ['pt' => 'Esqueceu a senha? Peça ao administrador para redefinir.',
                              'en' => 'Forgot your password? Ask the administrator to reset it.',
                              'es' => '¿Olvidó su contraseña? Pida al administrador que la restablezca.'],

        // password change
        'pw_title'        => ['pt' => 'Alterar senha', 'en' => 'Change password', 'es' => 'Cambiar contraseña'],
        'pw_must_change'  => ['pt' => 'Você está usando a senha temporária. Crie uma senha pessoal para continuar.',
                              'en' => 'You are using the temporary password. Create a personal password to continue.',
                              'es' => 'Está usando la contraseña temporal. Cree una contraseña personal para continuar.'],
        'pw_current'      => ['pt' => 'Senha atual', 'en' => 'Current password', 'es' => 'Contraseña actual'],
        'pw_new'          => ['pt' => 'Nova senha', 'en' => 'New password', 'es' => 'Nueva contraseña'],
        'pw_confirm'      => ['pt' => 'Confirmar nova senha', 'en' => 'Confirm new password', 'es' => 'Confirmar nueva contraseña'],
        'pw_rules'        => ['pt' => 'Mínimo de {n} caracteres, com letras e números.',
                              'en' => 'At least {n} characters, with letters and numbers.',
                              'es' => 'Mínimo {n} caracteres, con letras y números.'],
        'pw_wrong_current'=> ['pt' => 'A senha atual está incorreta.', 'en' => 'The current password is incorrect.',
                              'es' => 'La contraseña actual es incorrecta.'],
        'pw_too_short'    => ['pt' => 'A nova senha é curta demais.', 'en' => 'The new password is too short.',
                              'es' => 'La nueva contraseña es demasiado corta.'],
        'pw_is_default'   => ['pt' => 'A nova senha não pode ser a senha temporária.',
                              'en' => 'The new password cannot be the temporary password.',
                              'es' => 'La nueva contraseña no puede ser la temporal.'],
        'pw_needs_mix'    => ['pt' => 'Use letras e números na nova senha.', 'en' => 'Use both letters and numbers.',
                              'es' => 'Use letras y números en la nueva contraseña.'],
        'pw_mismatch'     => ['pt' => 'A confirmação não confere com a nova senha.',
                              'en' => 'The confirmation does not match the new password.',
                              'es' => 'La confirmación no coincide con la nueva contraseña.'],
        'pw_same_as_old'  => ['pt' => 'A nova senha deve ser diferente da atual.',
                              'en' => 'The new password must be different from the current one.',
                              'es' => 'La nueva contraseña debe ser diferente de la actual.'],
        'pw_changed'      => ['pt' => 'Senha alterada com sucesso.', 'en' => 'Password changed successfully.',
                              'es' => 'Contraseña cambiada correctamente.'],

        // employee home (stage 1 placeholder)
        'hello'           => ['pt' => 'Olá, {name}', 'en' => 'Hello, {name}', 'es' => 'Hola, {name}'],
        'punch_soon'      => ['pt' => 'O registro de ponto será liberado em breve. Seu acesso já está pronto.',
                              'en' => 'Clocking in will be available soon. Your access is ready.',
                              'es' => 'El registro de horario estará disponible pronto. Su acceso ya está listo.'],

        // admin — users
        'users'           => ['pt' => 'Usuários', 'en' => 'Users', 'es' => 'Usuarios'],
        'import'          => ['pt' => 'Importar equipe', 'en' => 'Import team', 'es' => 'Importar equipo'],
        'new_user'        => ['pt' => 'Novo usuário', 'en' => 'New user', 'es' => 'Nuevo usuario'],
        'edit_user'       => ['pt' => 'Editar usuário', 'en' => 'Edit user', 'es' => 'Editar usuario'],
        'full_name'       => ['pt' => 'Nome completo', 'en' => 'Full name', 'es' => 'Nombre completo'],
        'username_hint'   => ['pt' => 'Deixe em branco para gerar automaticamente (nome.sobrenome).',
                              'en' => 'Leave blank to generate it automatically (firstname.lastname).',
                              'es' => 'Déjelo en blanco para generarlo automáticamente (nombre.apellido).'],
        'role'            => ['pt' => 'Perfil', 'en' => 'Role', 'es' => 'Perfil'],
        'role_admin'      => ['pt' => 'Administrador', 'en' => 'Administrator', 'es' => 'Administrador'],
        'role_employee'   => ['pt' => 'Funcionário', 'en' => 'Employee', 'es' => 'Empleado'],
        'work_mode'       => ['pt' => 'Modo de trabalho', 'en' => 'Work mode', 'es' => 'Modalidad de trabajo'],
        'wm_office'       => ['pt' => 'Escritório (dentro do raio)', 'en' => 'Office (inside the radius)', 'es' => 'Oficina (dentro del radio)'],
        'wm_field'        => ['pt' => 'Externo (qualquer lugar, com GPS)', 'en' => 'Field (anywhere, GPS recorded)', 'es' => 'Externo (cualquier lugar, con GPS)'],
        'wm_flexible'     => ['pt' => 'Flexível (GPS opcional)', 'en' => 'Flexible (GPS optional)', 'es' => 'Flexible (GPS opcional)'],
        'linked_employee' => ['pt' => 'Vínculo com a equipe do site', 'en' => 'Linked website team member', 'es' => 'Vínculo con el equipo del sitio'],
        'none'            => ['pt' => '— nenhum —', 'en' => '— none —', 'es' => '— ninguno —'],
        'active'          => ['pt' => 'Ativo', 'en' => 'Active', 'es' => 'Activo'],
        'inactive'        => ['pt' => 'Inativo', 'en' => 'Inactive', 'es' => 'Inactivo'],
        'status'          => ['pt' => 'Situação', 'en' => 'Status', 'es' => 'Estado'],
        'last_login'      => ['pt' => 'Último acesso', 'en' => 'Last login', 'es' => 'Último acceso'],
        'never'           => ['pt' => 'nunca', 'en' => 'never', 'es' => 'nunca'],
        'temp_password'   => ['pt' => 'senha temporária', 'en' => 'temporary password', 'es' => 'contraseña temporal'],
        'edit'            => ['pt' => 'Editar', 'en' => 'Edit', 'es' => 'Editar'],
        'search'          => ['pt' => 'Buscar nome ou usuário', 'en' => 'Search name or username', 'es' => 'Buscar nombre o usuario'],
        'all'             => ['pt' => 'Todos', 'en' => 'All', 'es' => 'Todos'],
        'no_users'        => ['pt' => 'Nenhum usuário encontrado.', 'en' => 'No users found.', 'es' => 'No se encontraron usuarios.'],
        'reason'          => ['pt' => 'Motivo da alteração', 'en' => 'Reason for the change', 'es' => 'Motivo del cambio'],
        'reason_hint'     => ['pt' => 'Fica registrado no histórico de auditoria.', 'en' => 'Saved in the audit log.',
                              'es' => 'Queda registrado en el historial de auditoría.'],
        'user_created'    => ['pt' => 'Usuário {u} criado. Senha temporária: {p}', 'en' => 'User {u} created. Temporary password: {p}',
                              'es' => 'Usuario {u} creado. Contraseña temporal: {p}'],
        'user_saved'      => ['pt' => 'Alterações salvas.', 'en' => 'Changes saved.', 'es' => 'Cambios guardados.'],
        'reset_pw'        => ['pt' => 'Redefinir senha', 'en' => 'Reset password', 'es' => 'Restablecer contraseña'],
        'reset_pw_help'   => ['pt' => 'A senha volta a ser {p} e a pessoa terá que criar uma nova no próximo acesso.',
                              'en' => 'The password goes back to {p} and the person must create a new one at next sign-in.',
                              'es' => 'La contraseña vuelve a ser {p} y la persona deberá crear una nueva en el próximo acceso.'],
        'reset_pw_done'   => ['pt' => 'Senha de {u} redefinida para {p}.', 'en' => 'Password for {u} reset to {p}.',
                              'es' => 'Contraseña de {u} restablecida a {p}.'],
        'confirm_reset'   => ['pt' => 'Redefinir a senha deste usuário?', 'en' => 'Reset this user\'s password?',
                              'es' => '¿Restablecer la contraseña de este usuario?'],
        'err_name'        => ['pt' => 'Informe o nome completo.', 'en' => 'Enter the full name.', 'es' => 'Indique el nombre completo.'],
        'err_username'    => ['pt' => 'Usuário inválido: use letras minúsculas, números e pontos.',
                              'en' => 'Invalid username: use lowercase letters, numbers and dots.',
                              'es' => 'Usuario inválido: use minúsculas, números y puntos.'],
        'err_username_taken'=> ['pt' => 'Este usuário já existe.', 'en' => 'This username already exists.', 'es' => 'Este usuario ya existe.'],
        'err_employee_taken'=> ['pt' => 'Este membro da equipe já está vinculado a outro usuário.',
                                'en' => 'This team member is already linked to another user.',
                                'es' => 'Este miembro del equipo ya está vinculado a otro usuario.'],
        'err_self_admin'  => ['pt' => 'Você não pode remover seu próprio acesso de administrador nem desativar sua conta.',
                              'en' => 'You cannot remove your own admin access or deactivate your own account.',
                              'es' => 'No puede quitar su propio acceso de administrador ni desactivar su cuenta.'],
        'err_reason'      => ['pt' => 'Informe o motivo da alteração.', 'en' => 'Enter the reason for the change.',
                              'es' => 'Indique el motivo del cambio.'],
        'not_found'       => ['pt' => 'Usuário não encontrado.', 'en' => 'User not found.', 'es' => 'Usuario no encontrado.'],

        // admin — import
        'import_intro'    => ['pt' => 'Membros da equipe do site que ainda não têm acesso ao ponto. Cada um recebe o usuário nome.sobrenome e a senha temporária {p}.',
                              'en' => 'Website team members who don\'t have time-clock access yet. Each gets the username firstname.lastname and the temporary password {p}.',
                              'es' => 'Miembros del equipo del sitio que aún no tienen acceso. Cada uno recibe el usuario nombre.apellido y la contraseña temporal {p}.'],
        'import_none'     => ['pt' => 'Todos os membros da equipe do site já têm acesso.',
                              'en' => 'Every website team member already has access.',
                              'es' => 'Todos los miembros del equipo del sitio ya tienen acceso.'],
        'import_btn'      => ['pt' => 'Criar acessos selecionados', 'en' => 'Create selected accounts', 'es' => 'Crear accesos seleccionados'],
        'import_done'     => ['pt' => '{n} acesso(s) criado(s).', 'en' => '{n} account(s) created.', 'es' => '{n} acceso(s) creado(s).'],
        'position'        => ['pt' => 'Cargo', 'en' => 'Position', 'es' => 'Cargo'],
        'will_be'         => ['pt' => 'Usuário gerado', 'en' => 'Generated username', 'es' => 'Usuario generado'],

        // install
        'install_title'   => ['pt' => 'Instalação do VST Ponto', 'en' => 'VST Time Clock setup', 'es' => 'Instalación de VST Control Horario'],
    ];
}
