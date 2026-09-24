-- ============================================================
-- VST — Seed data: the 12 team members already on the site.
-- Import AFTER schema.sql (phpMyAdmin > Import).
-- Safe to run once. To reset, TRUNCATE employees; then re-import.
-- ============================================================

SET NAMES utf8mb4;

INSERT INTO employees
  (name, position_en, position_pt, position_es, department, phone, email, image, sort_order, is_active)
VALUES
('Fayan Abrams',
 'General Manager & Accountant', 'Gerente Geral e Contadora', 'Gerente General y Contadora',
 'Administration', '+592 673 5572', '', 'images/team/fayan-abrams.jpg', 10, 1),

('Gimel Carr',
 'VST Manager (Immigration & Passport Office Agent Assistant)',
 'Gerente VST (Assistente de Agente do Escritório de Imigração e Passaportes)',
 'Gerente VST (Asistente de Agente de la Oficina de Inmigración y Pasaportes)',
 'Administration', '+592 686 0040', '', 'images/team/gimel-carr.jpg', 20, 1),

('Gildineys Castillo',
 'Translator & VST Consultant for GGMC', 'Tradutora e Consultora VST para a GGMC', 'Traductora y Consultora VST para la GGMC',
 'Translation', '+592 6973569', '', 'images/team/gildineys-castillo.jpg', 30, 1),

('Rilda Silva de Oliveira Marques',
 'VST Translator & Receptionist', 'Tradutora e Recepcionista VST', 'Traductora y Recepcionista VST',
 'Customer Service', '+592 6636984', '', 'images/team/rilda-silva-de-oliveira-marques.jpg', 40, 1),

('Rakesh Rafael Carmelito Edmond',
 'Member / Sponsor', 'Membro / Patrocinador', 'Miembro / Patrocinador',
 'Administration', '', '', 'images/team/rakesh-rafael-edmond.jpg', 50, 1),

('Rakesh Cortney Edmond Junior',
 'Electrician Specialist', 'Especialista Eletricista', 'Especialista Electricista',
 'Other', '', '', 'images/team/rakesh-cortney-edmond-junior.jpg', 60, 1),

('Yara Karoline Silva',
 'Marketing Developer', 'Desenvolvedora de Marketing', 'Desarrolladora de Marketing',
 'Marketing', '', '', 'images/team/yara-karoline-silva.jpg', 70, 1),

('Yoanna Veliz Tortolo',
 'Office Assistant', 'Assistente de Escritório', 'Asistente de Oficina',
 'Administration', '', '', 'images/team/yoanna-veliz-tortolo.jpg', 80, 1),

('Yasmim Moreira Costa',
 'Marketing', 'Marketing', 'Marketing',
 'Marketing', '', '', 'images/team/yasmim-silva-moreira.jpg', 90, 1),

('Gemma Anita Webster',
 'Private Driver', 'Motorista Particular', 'Chofer Privado',
 'Other', '', '', 'images/team/gemma-anita-webster.jpg', 100, 1),

('Carmelito Clementino da Silva',
 'Consultation', 'Consultoria', 'Consultoría',
 'Other', '', '', 'images/team/carmelito-clementino-da-silva.jpg', 110, 1),

('Dixan Preval Aponte',
 'Immigration Services Coordinator / Translator',
 'Coordenador de Serviços de Imigração / Tradutor',
 'Coordinador de Servicios de Inmigración / Traductor',
 'Translation', '+592 6638064', '', 'images/team/dixan-preval-aponte.jpg', 120, 1);

-- ---- Contacts + social links -------------------------------
INSERT IGNORE INTO settings (skey, svalue) VALUES
('address',  'Lot 82 Albert St, Bourda, Georgetown, Guyana'),
('phone',    '+592-633-0603'),
('whatsapp', '+592-633-0603'),
('email',    'vstranslation2025@gmail.com'),
('hours',    ''),
('social_instagram',''),
('social_facebook',''),
('social_tiktok',''),
('social_youtube',''),
('social_linkedin',''),
('social_whatsapp','');

-- ---- Tourism destinations ----------------------------------
INSERT INTO destinations
  (name, location_en, location_pt, location_es, description_en, description_pt, description_es, image, sort_order, is_active)
VALUES
('Paruima Village',
 'Upper Mazaruni, Guyana', 'Alto Mazaruni, Guiana', 'Alto Mazaruni, Guyana',
 'A remote Amerindian village and home of the Arekuna people, surrounded by rainforest, rivers and stunning waterfalls in the Upper Mazaruni.',
 'Uma aldeia indígena remota e lar do povo Arekuna, cercada por floresta tropical, rios e cachoeiras deslumbrantes no Alto Mazaruni.',
 'Una aldea amerindia remota y hogar del pueblo Arekuna, rodeada de selva tropical, ríos y cascadas impresionantes en el Alto Mazaruni.',
 'images/tourism/paruima-village.jpg', 10, 1),

('Malali Reservation',
 'Demerara River, Guyana', 'Rio Demerara, Guiana', 'Río Demerara, Guyana',
 'A peaceful riverside Amerindian community surrounded by rainforest, with river beaches and a relaxing natural setting to unwind and explore.',
 'Uma comunidade indígena tranquila às margens do rio, cercada por floresta tropical, com praias de rio e um ambiente natural relaxante para descansar e explorar.',
 'Una tranquila comunidad amerindia a orillas del río, rodeada de selva tropical, con playas de río y un ambiente natural relajante para descansar y explorar.',
 'images/tourism/malali-reservation.jpg', 20, 1);

-- ---- Tourism gallery ---------------------------------------
INSERT INTO gallery (image, caption, sort_order, is_active) VALUES
('images/gallery/paruima-1.jpg', 'Paruima Village — valley view', 10, 1),
('images/gallery/paruima-2.jpg', 'Welcome to Paruima — Home of the Arekuna People', 20, 1),
('images/gallery/paruima-3.jpg', 'Waterfall near Paruima Village', 30, 1),
('images/gallery/paruima-4.jpg', 'Paruima Village from above', 40, 1),
('images/gallery/paruima-5.jpg', 'Rapids near Paruima Village', 50, 1),
('images/gallery/malali-1.jpg',  'Sunset over Malali Reservation', 60, 1),
('images/gallery/malali-2.jpg',  'Historic ruins at Malali Reservation', 70, 1),
('images/gallery/malali-3.jpg',  'Riverside at Malali Reservation', 80, 1);
