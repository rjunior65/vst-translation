/* =================================================================
   VST TRANSLATION — script.js  (Vanilla JavaScript, no frameworks)
   =================================================================
   Sections in this file:
     1) CONFIGURATION  ......  ← edit phones, emails, address, team, social
     2) TRANSLATIONS   ......  ← edit EN / PT / ES texts
     3) APP LOGIC      ......  ← you normally do NOT need to touch this
   ================================================================= */


/* =================================================================
   1) CONFIGURATION
   -----------------------------------------------------------------
   Fill in the values below. Leave a value as "" (empty) to HIDE it
   automatically on the website (no ugly empty fields for visitors).
   ================================================================= */

// ---- Company contact details (shown in the Contact section + footer) ----
const CONTACT_INFO = {
  address:  "Lot 82 Albert St, Bourda, Georgetown, Guyana",  // from the official logo
  phone:    "+592-633-0603",                 // from the official logo
  whatsapp: "+592-633-0603",                 // same number (clear this if it is NOT a WhatsApp line)
  email:    "vstranslation2025@gmail.com",   // main email
  hours:    ""                               // e.g. "Mon–Fri, 9:00–18:00"  (add when available)
};

// ---- Map (Contact section) ----
const MAP = {
  // Where to center the map. Use coordinates "lat,lng" (most precise) OR an
  // address. Leave "" to use CONTACT_INFO.address automatically.
  query:  "6.80889,-58.15370",   // exact VST location
  zoom:   17,
  // OPTIONAL: paste a Google Maps API key to use Google's official Embed API.
  // Leave "" to use the free, keyless map (no key required — recommended).
  apiKey: ""
};

// ---- CEO phone numbers ----
// Leave "" to hide the Phone and WhatsApp buttons for that CEO.
const CEO_PHONES = {
  dix:    "",   // Dix Aponte    — e.g. "+1 555 000 0001"
  vanusa: ""    // Vanusa Da Silva — e.g. "+1 555 000 0002"
};

// ---- Social media URLs ----
// Leave "" to hide that icon. Use full URLs (https://...).
const SOCIAL = {
  instagram: "",
  facebook:  "",
  tiktok:    "",
  youtube:   "",
  linkedin:  "",
  whatsapp:  ""   // optional: a wa.me link; if empty, the CONTACT_INFO.whatsapp is used
};

// =======================================================================
//  TEAM / EMPLOYEE LIST
//  -------------------------------------------------------------------
//  HOW TO EDIT:
//   • ADD a member    → copy one { ... } block, paste it, edit the values.
//   • REMOVE a member → delete its { ... } block (and the comma).
//   • PHOTO           → put the file in images/team/ and set "image".
//                       If the photo is missing, a placeholder is shown.
//   • DEPARTMENT      → must be one of:
//        "Administration", "Translation", "Tourism",
//        "Documentation", "Customer Service", "Other"
//        (this is what the filter buttons use).
//   • phone / email / description are optional → leave "" to hide.
//  -------------------------------------------------------------------
//  The examples below are PLACEHOLDERS — replace them with real people.
// =======================================================================
const employees = [
  {
    name:        "Fayan Abrams",
    position:    "General Manager & Accountant",
    department:  "Administration",
    phone:       "+592 673 5572",
    email:       "",
    description: "",
    image:       "images/team/fayan-abrams.jpg"
  },
  {
    name:        "Gimel Carr",
    position:    "VST Manager (Immigration & Passport Office Agent Assistant)",
    department:  "Administration",
    phone:       "+592 686 0040",
    email:       "",
    description: "",
    image:       "images/team/gimel-carr.jpg"
  },
  {
    name:        "Gildineys Castillo",
    position:    "Translator & VST Consultant for GGMC",
    department:  "Translation",
    phone:       "+592 6973569",
    email:       "",
    description: "",
    image:       "images/team/gildineys-castillo.jpg"
  },
  {
    name:        "Rilda Silva de Oliveira Marques",
    position:    "VST Translator & Receptionist",
    department:  "Customer Service",
    phone:       "+592 6636984",
    email:       "",
    description: "",
    image:       "images/team/rilda-silva-de-oliveira-marques.jpg"
  },
  {
    name:        "Rakesh Rafael Carmelito Edmond",
    position:    "Member / Sponsor",
    department:  "Administration",          // change if needed
    phone:       "",
    email:       "",
    description: "",
    image:       "images/team/rakesh-rafael-edmond.jpg"
  },
  {
    name:        "Rakesh Cortney Edmond Junior",
    position:    "Electrician Specialist",
    department:  "Other",                   // change if needed
    phone:       "",
    email:       "",
    description: "",
    image:       "images/team/rakesh-cortney-edmond-junior.jpg"
  },
  {
    name:        "Yara Karoline Silva",
    position:    "Marketing Developer",
    department:  "Marketing",
    phone:       "",
    email:       "",
    description: "",
    image:       "images/team/yara-karoline-silva.jpg"
  },
  {
    name:        "Yoanna Veliz Tortolo",
    position:    "Office Assistant",
    department:  "Administration",          // change if needed
    phone:       "",
    email:       "",
    description: "",
    image:       "images/team/yoanna-veliz-tortolo.jpg"
  },
  {
    name:        "Yasmim Moreira Costa",
    position:    "Marketing",
    department:  "Marketing",
    phone:       "",
    email:       "",
    description: "",
    image:       "images/team/yasmim-silva-moreira.jpg"
  },
  {
    name:        "Gemma Anita Webster",
    position:    "Private Driver",
    department:  "Other",                   // change if needed
    phone:       "",
    email:       "",
    description: "",
    image:       "images/team/gemma-anita-webster.jpg"
  },
  {
    name:        "Carmelito Clementino da Silva",
    position:    "Consultation",
    department:  "Other",                   // change if needed
    phone:       "",
    email:       "",
    description: "",
    image:       "images/team/carmelito-clementino-da-silva.jpg"
  },
  {
    name:        "Dixan Preval Aponte",
    position:    "Immigration Services Coordinator / Translator",
    department:  "Translation",             // change if needed
    phone:       "+592 6638064",
    email:       "",
    description: "",
    image:       "images/team/dixan-preval-aponte.jpg"
  }
  // ,{
  //   name: "New Person", position: "Role", department: "Customer Service",
  //   phone: "", email: "", description: "", image: "images/team/new-person.jpg"
  // }
];

// =======================================================================
//  JOB TITLE TRANSLATIONS  (Portuguese / Spanish)
//  -------------------------------------------------------------------
//  The "position" above is written in English. To show it translated,
//  add an entry here using the EXACT English text as the key.
//  If a position has no entry, the English text is shown as-is.
//  (English uses the text from the employees array directly.)
// =======================================================================
const POSITION_I18N = {
  "Member / Sponsor":        { pt: "Membro / Patrocinador",        es: "Miembro / Patrocinador" },
  "Electrician Specialist":  { pt: "Especialista Eletricista",     es: "Especialista Electricista" },
  "Marketing Developer":     { pt: "Desenvolvedora de Marketing",  es: "Desarrolladora de Marketing" },
  "Office Assistant":        { pt: "Assistente de Escritório",     es: "Asistente de Oficina" },
  "Marketing":               { pt: "Marketing",                    es: "Marketing" },
  "Private Driver":          { pt: "Motorista Particular",         es: "Chofer Privado" },
  "Consultation":            { pt: "Consultoria",                  es: "Consultoría" },
  "Immigration Services Coordinator / Translator": {
    pt: "Coordenador de Serviços de Imigração / Tradutor",
    es: "Coordinador de Servicios de Inmigración / Traductor"
  },
  "General Manager & Accountant": {
    pt: "Gerente Geral e Contadora",
    es: "Gerente General y Contadora"
  },
  "VST Manager (Immigration & Passport Office Agent Assistant)": {
    pt: "Gerente VST (Assistente de Agente do Escritório de Imigração e Passaportes)",
    es: "Gerente VST (Asistente de Agente de la Oficina de Inmigración y Pasaportes)"
  },
  "Translator & VST Consultant for GGMC": {
    pt: "Tradutora e Consultora VST para a GGMC",
    es: "Traductora y Consultora VST para la GGMC"
  },
  "VST Translator & Receptionist": {
    pt: "Tradutora e Recepcionista VST",
    es: "Traductora y Recepcionista VST"
  }
};


/* =================================================================
   2) TRANSLATIONS  (English = default)
   -----------------------------------------------------------------
   Every text on the site has a key. To change wording, edit the
   value here in the three languages. Keep the keys the same.
   ================================================================= */
const translations = {

  /* ---------------- ENGLISH ---------------- */
  en: {
    // Navigation
    nav_home: "Home", nav_about: "About", nav_services: "Services", nav_tourism: "Tourism",
    nav_leadership: "Leadership", nav_team: "Our Team", nav_faq: "FAQ", nav_contact: "Contact", nav_cta: "Contact Us",
    skip_link: "Skip to main content",
    // Hero
    hero_eyebrow: "Translation · Tourism · Professional Services",
    hero_title: "Connecting People, Languages & Opportunities",
    hero_desc: "Professional translation, documentation, tourism assistance and personalized services connecting people and opportunities.",
    hero_cta_services: "Explore Our Services", hero_cta_contact: "Contact VST",
    hero_badge_intl: "International", hero_badge_docs: "Documentation", hero_badge_travel: "Travel", hero_badge_langs: "3 Languages",
    // About
    about_eyebrow: "About Us", about_title: "About VST",
    about_lead: "We connect people, documents and destinations with professional, reliable and personalized service.",
    about_p1: "VST is a professional services company dedicated to helping people communicate, travel and handle documentation across languages and borders.",
    about_p2: "Our team combines translation expertise, tourism knowledge and documentation support to deliver a smooth, trustworthy experience for every client.",
    about_mission_t: "Mission", about_mission_d: "To connect people and opportunities through accurate translation and dedicated professional support.",
    about_vision_t: "Vision", about_vision_d: "To be a trusted international reference in translation, documentation and tourism services.",
    about_values_t: "Values", about_values_d: "Trust, professionalism, confidentiality, care for people and attention to every detail.",
    // Services
    services_eyebrow: "What We Do", services_title: "Our Services",
    services_lead: "A complete range of professional services designed around your needs.",
    service_translation_t: "Translation Services", service_translation_d: "Professional translation of personal, legal, business and immigration documents.",
    service_workpermit_t: "Work Permit Assistance", service_workpermit_d: "Guidance and support with documentation and preparation for Work Permit applications.",
    service_tourism_t: "Tourism Services", service_tourism_d: "Travel assistance, destinations and personalized tourism support.",
    service_docs_t: "Documentation Assistance", service_docs_d: "Help organizing, preparing and reviewing important documents.",
    service_other_t: "Other Professional Services", service_other_d: "Additional personalized services tailored to your specific situation.",
    btn_learn_more: "Learn More", btn_contact: "Contact", btn_contact_vst: "Contact VST",
    // Video
    video_eyebrow: "Get to Know Us", video_title: "Meet VST",
    video_lead: "Watch our presentation and discover how we can help you.",
    video_fallback: "Your browser does not support the video tag.",
    // Translation section
    tr_eyebrow: "Translation", tr_title: "Translation Services",
    tr_lead: "Accurate, professional and confidential translation for every kind of document.",
    tr_birth: "Birth Certificates", tr_marriage: "Marriage Certificates", tr_personal: "Personal Documents",
    tr_legal: "Legal Documents", tr_immigration: "Immigration Documents", tr_business: "Business Documents",
    tr_certificates: "Certificates", tr_other: "Other Documents",
    tr_portfolio_title: "Translation Examples", tr_portfolio_note: "Sample translations. All personal information is removed or blurred.",
    tr_cat_personal: "Personal Documents", tr_cat_certificates: "Certificates", tr_cat_immigration: "Immigration", tr_cat_legal: "Legal", tr_cat_certifications: "Certifications",
    tr_cta: "Request Translation Information",
    // Work Permit
    wp_eyebrow: "Assistance", wp_title: "Work Permit Assistance",
    wp_lead: "We assist with documentation and preparation for Work Permit applications.",
    wp_p1: "VST provides assistance with the documentation and preparation involved in Work Permit applications. We help you organize, translate and prepare the paperwork you may need.",
    wp_how_t: "How VST Assists",
    wp_how_1: "Reviewing and organizing your documents", wp_how_2: "Translating documents when required",
    wp_how_3: "Helping you understand the general process", wp_how_4: "Preparing your application paperwork",
    wp_docs_t: "Documents That May Be Necessary",
    wp_docs_1: "Identification and personal documents", wp_docs_2: "Certificates (birth, marriage, etc.)",
    wp_docs_3: "Professional or educational documents", wp_docs_4: "Other supporting documentation",
    wp_important_t: "Important Information",
    wp_important_d: "VST is a private services company. We are not a government agency and we do not guarantee approval of any application. We provide professional assistance with documentation and application preparation.",
    wp_cta_learn: "Learn More About Work Permit Assistance",
    // Tourism
    tour_eyebrow: "Explore", tour_title: "Tourism Services",
    tour_lead: "Discover destinations and travel with confidence and personalized support.",
    tour_d1_name: "Paruima Village", tour_d1_loc: "Upper Mazaruni, Guyana", tour_d1_desc: "A remote Amerindian village and home of the Arekuna people, surrounded by rainforest, rivers and stunning waterfalls in the Upper Mazaruni.",
    tour_d2_name: "Malali Reservation", tour_d2_loc: "Demerara River, Guyana", tour_d2_desc: "A peaceful riverside Amerindian community surrounded by rainforest, with river beaches and a relaxing natural setting to unwind and explore.",
    tour_d3_name: "Destination Name", tour_d3_loc: "Location", tour_d3_desc: "Short description of this destination. Add your own text and photos here.",
    tour_view: "View Photos", tour_request: "Request Information", gallery_title: "Tourism Gallery",
    // Other services
    other_eyebrow: "More", other_title: "Other Services", other_lead: "Additional professional services we are happy to provide.",
    other_1_t: "Document Organization", other_1_d: "Help gathering and organizing the paperwork you need.",
    other_2_t: "Personalized Guidance", other_2_d: "Personalized support tailored to your specific situation.",
    other_3_t: "Correspondence Support", other_3_d: "Assistance drafting and understanding official correspondence.",
    // Why
    why_eyebrow: "Why VST", why_title: "Why Choose VST",
    why_1_t: "Professional & Reliable", why_1_d: "Careful, professional work you can trust.",
    why_2_t: "Confidential", why_2_d: "Your documents and information are handled with full confidentiality.",
    why_3_t: "Multilingual", why_3_d: "We work across English, Portuguese and Spanish.",
    why_4_t: "Personalized Care", why_4_d: "Real people who care about helping you succeed.",
    // Leadership
    lead_eyebrow: "Leadership", lead_title: "Our Leadership", lead_lead: "Meet the people behind VST.",
    lead_role: "CEO", lead_email: "Email", lead_phone: "Phone", lead_whatsapp: "WhatsApp",
    // Team
    team_eyebrow: "People", team_title: "Our Team", team_lead: "The dedicated professionals behind VST.",
    team_f_all: "All", team_f_admin: "Administration", team_f_translation: "Translation", team_f_tourism: "Tourism",
    team_f_docs: "Documentation", team_f_cs: "Customer Service", team_f_marketing: "Marketing", team_f_other: "Other",
    team_empty: "No team members in this department yet.",
    // FAQ
    faq_eyebrow: "FAQ", faq_title: "Frequently Asked Questions", faq_lead: "Answers to the questions we hear most often.",
    faq_q1: "Which languages do you work with?",
    faq_a1: "We work with Portuguese, English and Spanish, helping you communicate across all three.",
    faq_q2: "How can I request a quote or more information?",
    faq_a2: "Use the contact form on this page, send us a message on WhatsApp, or email us. We will get back to you as soon as possible.",
    faq_q3: "What types of documents can you translate?",
    faq_a3: "We translate a wide range of documents, including personal, legal, business and immigration documents. Contact us with your specific case for details.",
    faq_q4: "How does Work Permit assistance work?",
    faq_a4: "We assist with organizing, translating and preparing the documentation involved in Work Permit applications. VST is a private company: we do not represent any government and do not guarantee approval.",
    faq_q5: "Do you also offer tourism services?",
    faq_a5: "Yes. We offer tourism assistance, destinations and personalized travel support. See the Tourism section or contact us for more information.",
    // Contact
    contact_eyebrow: "Get in Touch", contact_title: "Contact VST",
    contact_lead: "We would love to hear from you. Reach out through any channel below.",
    contact_address: "Address", contact_phone: "Main Phone", contact_whatsapp: "WhatsApp",
    contact_email: "Main Email", contact_hours: "Business Hours",
    contact_call: "Call Us", contact_emailus: "Email Us", contact_whatsapp_btn: "WhatsApp", contact_directions: "Get Directions",
    contact_map: "🗺️ Map will appear here once the address is added.",
    // Form
    form_name: "Full Name", form_name_ph: "Your full name", form_err_name: "Please enter your name.",
    form_phone: "Phone Number", form_phone_ph: "Your phone number",
    form_email: "Email", form_email_ph: "you@example.com", form_err_email: "Please enter a valid email.",
    form_lang: "Preferred Language", form_lang_en: "English", form_lang_pt: "Portuguese", form_lang_es: "Spanish",
    form_service: "Service Needed", form_srv_translation: "Translation", form_srv_workpermit: "Work Permit Assistance",
    form_srv_tourism: "Tourism", form_srv_docs: "Documentation", form_srv_other: "Other Services",
    form_message: "Message", form_message_ph: "How can we help you?", form_err_message: "Please enter a message.",
    form_submit: "Send via WhatsApp", form_hint: "Submitting opens WhatsApp with your message ready to send.",
    // QR
    qr_title: "Connect with VST", qr_text: "Scan the QR Code to visit our website.",
    qr_placeholder: "Add images/qr-code.png pointing to your final domain.",
    // Footer
    footer_about: "Professional translation, documentation, Work Permit assistance and tourism services connecting people and opportunities.",
    footer_nav: "Navigation", footer_services: "Services", footer_contact: "Contact",
    footer_rights: "All Rights Reserved.", footer_privacy: "Privacy Policy", footer_terms: "Terms of Use"
  },

  /* ---------------- PORTUGUÊS ---------------- */
  pt: {
    nav_home: "Início", nav_about: "Sobre", nav_services: "Serviços", nav_tourism: "Turismo",
    nav_leadership: "Liderança", nav_team: "Nossa Equipe", nav_faq: "FAQ", nav_contact: "Contato", nav_cta: "Fale Conosco",
    skip_link: "Pular para o conteúdo",
    hero_eyebrow: "Tradução · Turismo · Serviços Profissionais",
    hero_title: "Conectando Pessoas, Idiomas e Oportunidades",
    hero_desc: "Tradução profissional, documentação, assistência em turismo e serviços personalizados que conectam pessoas e oportunidades.",
    hero_cta_services: "Conheça os Serviços", hero_cta_contact: "Fale com a VST",
    hero_badge_intl: "Internacional", hero_badge_docs: "Documentação", hero_badge_travel: "Viagens", hero_badge_langs: "3 Idiomas",
    about_eyebrow: "Sobre Nós", about_title: "Sobre a VST",
    about_lead: "Conectamos pessoas, documentos e destinos com um serviço profissional, confiável e personalizado.",
    about_p1: "A VST é uma empresa de serviços profissionais dedicada a ajudar as pessoas a se comunicar, viajar e cuidar da documentação entre idiomas e fronteiras.",
    about_p2: "Nossa equipe une experiência em tradução, conhecimento em turismo e apoio em documentação para oferecer uma experiência tranquila e confiável a cada cliente.",
    about_mission_t: "Missão", about_mission_d: "Conectar pessoas e oportunidades por meio de traduções precisas e apoio profissional dedicado.",
    about_vision_t: "Visão", about_vision_d: "Ser uma referência internacional confiável em tradução, documentação e turismo.",
    about_values_t: "Valores", about_values_d: "Confiança, profissionalismo, confidencialidade, cuidado com as pessoas e atenção a cada detalhe.",
    services_eyebrow: "O Que Fazemos", services_title: "Nossos Serviços",
    services_lead: "Uma gama completa de serviços profissionais pensada para as suas necessidades.",
    service_translation_t: "Serviços de Tradução", service_translation_d: "Tradução profissional de documentos pessoais, jurídicos, empresariais e de imigração.",
    service_workpermit_t: "Assistência para Work Permit", service_workpermit_d: "Orientação e apoio na documentação e preparação de pedidos de Work Permit.",
    service_tourism_t: "Serviços de Turismo", service_tourism_d: "Assistência de viagem, destinos e apoio turístico personalizado.",
    service_docs_t: "Assistência em Documentação", service_docs_d: "Ajuda para organizar, preparar e revisar documentos importantes.",
    service_other_t: "Outros Serviços Profissionais", service_other_d: "Serviços personalizados adicionais adaptados à sua situação.",
    btn_learn_more: "Saiba Mais", btn_contact: "Contato", btn_contact_vst: "Fale com a VST",
    video_eyebrow: "Conheça-nos", video_title: "Conheça a VST",
    video_lead: "Assista à nossa apresentação e descubra como podemos ajudar você.",
    video_fallback: "Seu navegador não suporta vídeo.",
    tr_eyebrow: "Tradução", tr_title: "Serviços de Tradução",
    tr_lead: "Tradução precisa, profissional e confidencial para todo tipo de documento.",
    tr_birth: "Certidões de Nascimento", tr_marriage: "Certidões de Casamento", tr_personal: "Documentos Pessoais",
    tr_legal: "Documentos Jurídicos", tr_immigration: "Documentos de Imigração", tr_business: "Documentos Empresariais",
    tr_certificates: "Certificados", tr_other: "Outros Documentos",
    tr_portfolio_title: "Exemplos de Tradução", tr_portfolio_note: "Exemplos de traduções. Todas as informações pessoais foram removidas ou borradas.",
    tr_cat_personal: "Documentos Pessoais", tr_cat_certificates: "Certidões", tr_cat_immigration: "Imigração", tr_cat_legal: "Jurídico", tr_cat_certifications: "Certificações",
    tr_cta: "Solicitar Informações de Tradução",
    wp_eyebrow: "Assistência", wp_title: "Assistência para Work Permit",
    wp_lead: "Auxiliamos com a documentação e a preparação de pedidos de Work Permit.",
    wp_p1: "A VST oferece assistência com a documentação e a preparação envolvidas nos pedidos de Work Permit. Ajudamos a organizar, traduzir e preparar os documentos necessários.",
    wp_how_t: "Como a VST Ajuda",
    wp_how_1: "Revisar e organizar seus documentos", wp_how_2: "Traduzir documentos quando necessário",
    wp_how_3: "Ajudar você a entender o processo geral", wp_how_4: "Preparar a documentação do seu pedido",
    wp_docs_t: "Documentos Que Podem Ser Necessários",
    wp_docs_1: "Documentos de identificação e pessoais", wp_docs_2: "Certidões (nascimento, casamento, etc.)",
    wp_docs_3: "Documentos profissionais ou educacionais", wp_docs_4: "Outros documentos de apoio",
    wp_important_t: "Informação Importante",
    wp_important_d: "A VST é uma empresa privada de serviços. Não somos um órgão governamental e não garantimos a aprovação de qualquer pedido. Oferecemos assistência profissional com documentação e preparação de pedidos.",
    wp_cta_learn: "Saiba Mais Sobre a Assistência de Work Permit",
    tour_eyebrow: "Explore", tour_title: "Serviços de Turismo",
    tour_lead: "Descubra destinos e viaje com confiança e apoio personalizado.",
    tour_d1_name: "Paruima Village", tour_d1_loc: "Alto Mazaruni, Guiana", tour_d1_desc: "Uma aldeia indígena remota e lar do povo Arekuna, cercada por floresta tropical, rios e cachoeiras deslumbrantes no Alto Mazaruni.",
    tour_d2_name: "Malali Reservation", tour_d2_loc: "Rio Demerara, Guiana", tour_d2_desc: "Uma comunidade indígena tranquila às margens do rio, cercada por floresta tropical, com praias de rio e um ambiente natural relaxante para descansar e explorar.",
    tour_d3_name: "Nome do Destino", tour_d3_loc: "Localização", tour_d3_desc: "Breve descrição deste destino. Adicione seu próprio texto e fotos aqui.",
    tour_view: "Ver Fotos", tour_request: "Solicitar Informações", gallery_title: "Galeria de Turismo",
    other_eyebrow: "Mais", other_title: "Outros Serviços", other_lead: "Serviços profissionais adicionais que teremos prazer em oferecer.",
    other_1_t: "Organização de Documentos", other_1_d: "Ajuda para reunir e organizar a documentação necessária.",
    other_2_t: "Orientação Personalizada", other_2_d: "Apoio personalizado adaptado à sua situação específica.",
    other_3_t: "Apoio com Correspondências", other_3_d: "Assistência para redigir e entender correspondências oficiais.",
    why_eyebrow: "Por Que a VST", why_title: "Por Que Escolher a VST",
    why_1_t: "Profissional e Confiável", why_1_d: "Trabalho cuidadoso e profissional em que você pode confiar.",
    why_2_t: "Confidencial", why_2_d: "Seus documentos e informações são tratados com total confidencialidade.",
    why_3_t: "Multilíngue", why_3_d: "Trabalhamos em inglês, português e espanhol.",
    why_4_t: "Atendimento Personalizado", why_4_d: "Pessoas reais que se importam em ajudar você a ter sucesso.",
    lead_eyebrow: "Liderança", lead_title: "Nossa Liderança", lead_lead: "Conheça as pessoas por trás da VST.",
    lead_role: "CEO", lead_email: "E-mail", lead_phone: "Telefone", lead_whatsapp: "WhatsApp",
    team_eyebrow: "Pessoas", team_title: "Nossa Equipe", team_lead: "Os profissionais dedicados por trás da VST.",
    team_f_all: "Todos", team_f_admin: "Administração", team_f_translation: "Tradução", team_f_tourism: "Turismo",
    team_f_docs: "Documentação", team_f_cs: "Atendimento", team_f_marketing: "Marketing", team_f_other: "Outro",
    team_empty: "Ainda não há membros neste departamento.",
    // FAQ
    faq_eyebrow: "Dúvidas", faq_title: "Perguntas Frequentes", faq_lead: "Respostas para as perguntas que mais recebemos.",
    faq_q1: "Com quais idiomas vocês trabalham?",
    faq_a1: "Trabalhamos com português, inglês e espanhol, ajudando você a se comunicar nos três idiomas.",
    faq_q2: "Como posso solicitar um orçamento ou mais informações?",
    faq_a2: "Use o formulário de contato desta página, envie uma mensagem no WhatsApp ou um e-mail. Responderemos o mais rápido possível.",
    faq_q3: "Que tipos de documentos vocês traduzem?",
    faq_a3: "Traduzimos uma ampla variedade de documentos, incluindo pessoais, jurídicos, empresariais e de imigração. Fale conosco com o seu caso específico para mais detalhes.",
    faq_q4: "Como funciona a assistência para Work Permit?",
    faq_a4: "Ajudamos a organizar, traduzir e preparar a documentação envolvida nos pedidos de Work Permit. A VST é uma empresa privada: não representamos nenhum governo e não garantimos aprovação.",
    faq_q5: "Vocês também oferecem serviços de turismo?",
    faq_a5: "Sim. Oferecemos assistência de turismo, destinos e apoio de viagem personalizado. Veja a seção de Turismo ou fale conosco para mais informações.",
    contact_eyebrow: "Fale Conosco", contact_title: "Contato VST",
    contact_lead: "Adoraríamos ouvir você. Fale conosco por qualquer canal abaixo.",
    contact_address: "Endereço", contact_phone: "Telefone Principal", contact_whatsapp: "WhatsApp",
    contact_email: "E-mail Principal", contact_hours: "Horário de Atendimento",
    contact_call: "Ligar", contact_emailus: "Enviar E-mail", contact_whatsapp_btn: "WhatsApp", contact_directions: "Como Chegar",
    contact_map: "🗺️ O mapa aparecerá aqui assim que o endereço for adicionado.",
    form_name: "Nome Completo", form_name_ph: "Seu nome completo", form_err_name: "Por favor, informe seu nome.",
    form_phone: "Telefone", form_phone_ph: "Seu telefone",
    form_email: "E-mail", form_email_ph: "voce@exemplo.com", form_err_email: "Informe um e-mail válido.",
    form_lang: "Idioma Preferido", form_lang_en: "Inglês", form_lang_pt: "Português", form_lang_es: "Espanhol",
    form_service: "Serviço Desejado", form_srv_translation: "Tradução", form_srv_workpermit: "Assistência Work Permit",
    form_srv_tourism: "Turismo", form_srv_docs: "Documentação", form_srv_other: "Outros Serviços",
    form_message: "Mensagem", form_message_ph: "Como podemos ajudar você?", form_err_message: "Por favor, escreva uma mensagem.",
    form_submit: "Enviar via WhatsApp", form_hint: "Ao enviar, o WhatsApp abre com sua mensagem pronta.",
    qr_title: "Conecte-se com a VST", qr_text: "Escaneie o QR Code para visitar nosso site.",
    qr_placeholder: "Adicione images/qr-code.png apontando para o domínio final.",
    footer_about: "Tradução profissional, documentação, assistência para Work Permit e serviços de turismo que conectam pessoas e oportunidades.",
    footer_nav: "Navegação", footer_services: "Serviços", footer_contact: "Contato",
    footer_rights: "Todos os Direitos Reservados.", footer_privacy: "Política de Privacidade", footer_terms: "Termos de Uso"
  },

  /* ---------------- ESPAÑOL ---------------- */
  es: {
    nav_home: "Inicio", nav_about: "Nosotros", nav_services: "Servicios", nav_tourism: "Turismo",
    nav_leadership: "Liderazgo", nav_team: "Nuestro Equipo", nav_faq: "FAQ", nav_contact: "Contacto", nav_cta: "Contáctanos",
    skip_link: "Saltar al contenido",
    hero_eyebrow: "Traducción · Turismo · Servicios Profesionales",
    hero_title: "Conectando Personas, Idiomas y Oportunidades",
    hero_desc: "Traducción profesional, documentación, asistencia turística y servicios personalizados que conectan personas y oportunidades.",
    hero_cta_services: "Explorar Servicios", hero_cta_contact: "Contactar VST",
    hero_badge_intl: "Internacional", hero_badge_docs: "Documentación", hero_badge_travel: "Viajes", hero_badge_langs: "3 Idiomas",
    about_eyebrow: "Nosotros", about_title: "Sobre VST",
    about_lead: "Conectamos personas, documentos y destinos con un servicio profesional, confiable y personalizado.",
    about_p1: "VST es una empresa de servicios profesionales dedicada a ayudar a las personas a comunicarse, viajar y gestionar su documentación entre idiomas y fronteras.",
    about_p2: "Nuestro equipo combina experiencia en traducción, conocimiento turístico y apoyo en documentación para ofrecer una experiencia fluida y confiable a cada cliente.",
    about_mission_t: "Misión", about_mission_d: "Conectar personas y oportunidades mediante traducciones precisas y un apoyo profesional dedicado.",
    about_vision_t: "Visión", about_vision_d: "Ser una referencia internacional confiable en traducción, documentación y turismo.",
    about_values_t: "Valores", about_values_d: "Confianza, profesionalismo, confidencialidad, cuidado por las personas y atención a cada detalle.",
    services_eyebrow: "Qué Hacemos", services_title: "Nuestros Servicios",
    services_lead: "Una gama completa de servicios profesionales pensada para tus necesidades.",
    service_translation_t: "Servicios de Traducción", service_translation_d: "Traducción profesional de documentos personales, legales, empresariales y de inmigración.",
    service_workpermit_t: "Asistencia para Work Permit", service_workpermit_d: "Orientación y apoyo con la documentación y preparación de solicitudes de Work Permit.",
    service_tourism_t: "Servicios de Turismo", service_tourism_d: "Asistencia de viaje, destinos y apoyo turístico personalizado.",
    service_docs_t: "Asistencia en Documentación", service_docs_d: "Ayuda para organizar, preparar y revisar documentos importantes.",
    service_other_t: "Otros Servicios Profesionales", service_other_d: "Servicios personalizados adicionales adaptados a tu situación.",
    btn_learn_more: "Saber Más", btn_contact: "Contacto", btn_contact_vst: "Contactar VST",
    video_eyebrow: "Conócenos", video_title: "Conoce VST",
    video_lead: "Mira nuestra presentación y descubre cómo podemos ayudarte.",
    video_fallback: "Tu navegador no admite video.",
    tr_eyebrow: "Traducción", tr_title: "Servicios de Traducción",
    tr_lead: "Traducción precisa, profesional y confidencial para todo tipo de documento.",
    tr_birth: "Actas de Nacimiento", tr_marriage: "Actas de Matrimonio", tr_personal: "Documentos Personales",
    tr_legal: "Documentos Legales", tr_immigration: "Documentos de Inmigración", tr_business: "Documentos Empresariales",
    tr_certificates: "Certificados", tr_other: "Otros Documentos",
    tr_portfolio_title: "Ejemplos de Traducción", tr_portfolio_note: "Ejemplos de traducciones. Toda la información personal fue eliminada o difuminada.",
    tr_cat_personal: "Documentos Personales", tr_cat_certificates: "Certificados", tr_cat_immigration: "Inmigración", tr_cat_legal: "Legal", tr_cat_certifications: "Certificaciones",
    tr_cta: "Solicitar Información de Traducción",
    wp_eyebrow: "Asistencia", wp_title: "Asistencia para Work Permit",
    wp_lead: "Ayudamos con la documentación y la preparación de solicitudes de Work Permit.",
    wp_p1: "VST ofrece asistencia con la documentación y la preparación de las solicitudes de Work Permit. Te ayudamos a organizar, traducir y preparar los documentos que puedas necesitar.",
    wp_how_t: "Cómo Ayuda VST",
    wp_how_1: "Revisar y organizar tus documentos", wp_how_2: "Traducir documentos cuando sea necesario",
    wp_how_3: "Ayudarte a entender el proceso general", wp_how_4: "Preparar la documentación de tu solicitud",
    wp_docs_t: "Documentos Que Pueden Ser Necesarios",
    wp_docs_1: "Documentos de identificación y personales", wp_docs_2: "Certificados (nacimiento, matrimonio, etc.)",
    wp_docs_3: "Documentos profesionales o educativos", wp_docs_4: "Otra documentación de apoyo",
    wp_important_t: "Información Importante",
    wp_important_d: "VST es una empresa privada de servicios. No somos una agencia gubernamental y no garantizamos la aprobación de ninguna solicitud. Ofrecemos asistencia profesional con la documentación y la preparación de solicitudes.",
    wp_cta_learn: "Saber Más Sobre la Asistencia de Work Permit",
    tour_eyebrow: "Explora", tour_title: "Servicios de Turismo",
    tour_lead: "Descubre destinos y viaja con confianza y apoyo personalizado.",
    tour_d1_name: "Paruima Village", tour_d1_loc: "Alto Mazaruni, Guyana", tour_d1_desc: "Una aldea amerindia remota y hogar del pueblo Arekuna, rodeada de selva tropical, ríos y cascadas impresionantes en el Alto Mazaruni.",
    tour_d2_name: "Malali Reservation", tour_d2_loc: "Río Demerara, Guyana", tour_d2_desc: "Una tranquila comunidad amerindia a orillas del río, rodeada de selva tropical, con playas de río y un ambiente natural relajante para descansar y explorar.",
    tour_d3_name: "Nombre del Destino", tour_d3_loc: "Ubicación", tour_d3_desc: "Breve descripción de este destino. Agrega tu propio texto y fotos aquí.",
    tour_view: "Ver Fotos", tour_request: "Solicitar Información", gallery_title: "Galería de Turismo",
    other_eyebrow: "Más", other_title: "Otros Servicios", other_lead: "Servicios profesionales adicionales que ofrecemos con gusto.",
    other_1_t: "Organización de Documentos", other_1_d: "Ayuda para reunir y organizar la documentación que necesitas.",
    other_2_t: "Orientación Personalizada", other_2_d: "Apoyo personalizado adaptado a tu situación específica.",
    other_3_t: "Apoyo con Correspondencia", other_3_d: "Asistencia para redactar y entender correspondencia oficial.",
    why_eyebrow: "Por Qué VST", why_title: "Por Qué Elegir VST",
    why_1_t: "Profesional y Confiable", why_1_d: "Trabajo cuidadoso y profesional en el que puedes confiar.",
    why_2_t: "Confidencial", why_2_d: "Tus documentos e información se tratan con total confidencialidad.",
    why_3_t: "Multilingüe", why_3_d: "Trabajamos en inglés, portugués y español.",
    why_4_t: "Atención Personalizada", why_4_d: "Personas reales que se preocupan por ayudarte a tener éxito.",
    lead_eyebrow: "Liderazgo", lead_title: "Nuestro Liderazgo", lead_lead: "Conoce a las personas detrás de VST.",
    lead_role: "CEO", lead_email: "Correo", lead_phone: "Teléfono", lead_whatsapp: "WhatsApp",
    team_eyebrow: "Personas", team_title: "Nuestro Equipo", team_lead: "Los profesionales dedicados detrás de VST.",
    team_f_all: "Todos", team_f_admin: "Administración", team_f_translation: "Traducción", team_f_tourism: "Turismo",
    team_f_docs: "Documentación", team_f_cs: "Atención al Cliente", team_f_marketing: "Marketing", team_f_other: "Otro",
    team_empty: "Aún no hay miembros en este departamento.",
    // FAQ
    faq_eyebrow: "Preguntas", faq_title: "Preguntas Frecuentes", faq_lead: "Respuestas a las preguntas que más nos hacen.",
    faq_q1: "¿Con qué idiomas trabajan?",
    faq_a1: "Trabajamos con portugués, inglés y español, ayudándote a comunicarte en los tres idiomas.",
    faq_q2: "¿Cómo puedo solicitar un presupuesto o más información?",
    faq_a2: "Usa el formulario de contacto de esta página, envíanos un mensaje por WhatsApp o un correo. Te responderemos lo antes posible.",
    faq_q3: "¿Qué tipos de documentos traducen?",
    faq_a3: "Traducimos una amplia variedad de documentos, incluidos personales, legales, empresariales y de inmigración. Contáctanos con tu caso específico para más detalles.",
    faq_q4: "¿Cómo funciona la asistencia para Work Permit?",
    faq_a4: "Ayudamos a organizar, traducir y preparar la documentación de las solicitudes de Work Permit. VST es una empresa privada: no representamos a ningún gobierno y no garantizamos la aprobación.",
    faq_q5: "¿También ofrecen servicios de turismo?",
    faq_a5: "Sí. Ofrecemos asistencia turística, destinos y apoyo de viaje personalizado. Consulta la sección de Turismo o contáctanos para más información.",
    contact_eyebrow: "Contáctanos", contact_title: "Contacto VST",
    contact_lead: "Nos encantaría saber de ti. Escríbenos por cualquier canal a continuación.",
    contact_address: "Dirección", contact_phone: "Teléfono Principal", contact_whatsapp: "WhatsApp",
    contact_email: "Correo Principal", contact_hours: "Horario de Atención",
    contact_call: "Llamar", contact_emailus: "Enviar Correo", contact_whatsapp_btn: "WhatsApp", contact_directions: "Cómo Llegar",
    contact_map: "🗺️ El mapa aparecerá aquí una vez que se agregue la dirección.",
    form_name: "Nombre Completo", form_name_ph: "Tu nombre completo", form_err_name: "Por favor, ingresa tu nombre.",
    form_phone: "Teléfono", form_phone_ph: "Tu teléfono",
    form_email: "Correo", form_email_ph: "tu@ejemplo.com", form_err_email: "Ingresa un correo válido.",
    form_lang: "Idioma Preferido", form_lang_en: "Inglés", form_lang_pt: "Portugués", form_lang_es: "Español",
    form_service: "Servicio Requerido", form_srv_translation: "Traducción", form_srv_workpermit: "Asistencia Work Permit",
    form_srv_tourism: "Turismo", form_srv_docs: "Documentación", form_srv_other: "Otros Servicios",
    form_message: "Mensaje", form_message_ph: "¿Cómo podemos ayudarte?", form_err_message: "Por favor, escribe un mensaje.",
    form_submit: "Enviar por WhatsApp", form_hint: "Al enviar, se abre WhatsApp con tu mensaje listo.",
    qr_title: "Conéctate con VST", qr_text: "Escanea el Código QR para visitar nuestro sitio.",
    qr_placeholder: "Agrega images/qr-code.png apuntando a tu dominio final.",
    footer_about: "Traducción profesional, documentación, asistencia para Work Permit y servicios de turismo que conectan personas y oportunidades.",
    footer_nav: "Navegación", footer_services: "Servicios", footer_contact: "Contacto",
    footer_rights: "Todos los Derechos Reservados.", footer_privacy: "Política de Privacidad", footer_terms: "Términos de Uso"
  }
};

// WhatsApp greeting message per language (used by floating button & CEO buttons)
const WA_GREETING = {
  en: "Hello! I found VST through your website and would like more information about your services.",
  pt: "Olá! Encontrei a VST pelo site e gostaria de mais informações sobre os serviços.",
  es: "¡Hola! Encontré VST a través del sitio web y me gustaría obtener más información sobre sus servicios."
};


/* =================================================================
   3) APP LOGIC   (you normally do not need to edit below this line)
   ================================================================= */
(function () {
  "use strict";

  const SUPPORTED = ["en", "pt", "es"];
  let currentLang = "en";

  // ---------- helpers ----------
  const $  = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));
  const t  = (key) => (translations[currentLang] && translations[currentLang][key]) || translations.en[key] || "";
  const digitsOnly = (s) => (s || "").replace(/[^\d]/g, "");           // for wa.me (no +, no spaces)
  const telHref    = (s) => "tel:" + (s || "").replace(/[^\d+]/g, ""); // keep leading +

  /* ---------------- i18n ---------------- */
  function applyLanguage(lang) {
    if (!SUPPORTED.includes(lang)) lang = "en";
    currentLang = lang;
    document.documentElement.lang = lang;

    // Text content
    $$("[data-i18n]").forEach((el) => {
      const val = t(el.getAttribute("data-i18n"));
      if (val) el.textContent = val;
    });
    // Placeholders
    $$("[data-i18n-placeholder]").forEach((el) => {
      const val = t(el.getAttribute("data-i18n-placeholder"));
      if (val) el.setAttribute("placeholder", val);
    });
    // aria-labels
    $$("[data-i18n-aria]").forEach((el) => {
      const val = t(el.getAttribute("data-i18n-aria"));
      if (val) el.setAttribute("aria-label", val);
    });

    // Active state on all language buttons
    $$(".lang-btn").forEach((b) => b.classList.toggle("active", b.dataset.lang === lang));

    // Re-render team (department badges are translated)
    renderTeam(getActiveFilter());
    // Re-render DB-driven destinations so location/description follow the language
    if (activeDestinations) renderDestinations(activeDestinations);
    // Refresh WhatsApp links (greeting text changes with language)
    refreshWhatsApp();

    localStorage.setItem("vst_lang", lang);
  }

  function initLanguage() {
    const saved = localStorage.getItem("vst_lang");
    applyLanguage(SUPPORTED.includes(saved) ? saved : "en");
    $$(".lang-btn").forEach((btn) => {
      btn.addEventListener("click", () => applyLanguage(btn.dataset.lang));
    });
  }

  /* ---------------- Mobile menu ---------------- */
  function initMenu() {
    const burger = $("#hamburger");
    const nav = $("#mainNav");
    if (!burger || !nav) return;

    const close = () => { nav.classList.remove("open"); burger.classList.remove("open"); burger.setAttribute("aria-expanded", "false"); };
    const toggle = () => {
      const open = nav.classList.toggle("open");
      burger.classList.toggle("open", open);
      burger.setAttribute("aria-expanded", String(open));
    };

    burger.addEventListener("click", toggle);
    // Close when a link (or the CTA) is tapped
    $$("#mainNav a").forEach((a) => a.addEventListener("click", close));
    // Close on Escape
    document.addEventListener("keydown", (e) => { if (e.key === "Escape") close(); });
  }

  /* ---------------- Team rendering + filter ---------------- */
  const DEPT_KEY = {
    "Administration": "team_f_admin", "Translation": "team_f_translation", "Tourism": "team_f_tourism",
    "Documentation": "team_f_docs", "Customer Service": "team_f_cs", "Marketing": "team_f_marketing", "Other": "team_f_other"
  };

  function getActiveFilter() {
    const active = $("#teamFilter .filter-btn.is-active");
    return active ? active.dataset.filter : "all";
  }

  // Translate a job title for the current language (falls back to the English text)
  function translatePosition(pos) {
    if (!pos) return "";
    if (currentLang === "en") return pos;
    const entry = (typeof POSITION_I18N !== "undefined") && POSITION_I18N[pos];
    return (entry && entry[currentLang]) || pos;
  }

  // The team list the site renders. Starts as the built-in list (works with no
  // server), and is replaced by live database data when api/employees.php answers.
  let activeEmployees = employees;

  // Position/description work with BOTH shapes: the built-in list (position string)
  // and the database (positions/descriptions objects per language).
  function empPosition(emp) {
    if (emp.positions && typeof emp.positions === "object")
      return emp.positions[currentLang] || emp.positions.en || "";
    return translatePosition(emp.position || "");
  }
  function empDescription(emp) {
    if (emp.descriptions && typeof emp.descriptions === "object")
      return emp.descriptions[currentLang] || emp.descriptions.en || "";
    return emp.description || "";
  }

  function renderTeam(filter = "all") {
    const grid = $("#teamGrid");
    const empty = $("#teamEmpty");
    if (!grid) return;

    const list = activeEmployees.filter((e) => filter === "all" || e.department === filter);
    grid.innerHTML = "";

    if (list.length === 0) { if (empty) empty.hidden = false; return; }
    if (empty) empty.hidden = true;

    list.forEach((emp) => {
      const deptLabel = t(DEPT_KEY[emp.department] || "team_f_other");
      const posLabel = empPosition(emp);
      const descLabel = empDescription(emp);
      const imgSrc = emp.image || "images/placeholder.svg";
      const card = document.createElement("article");
      card.className = "card team-card";
      card.setAttribute("data-department", emp.department);

      // Contact links (only shown if provided)
      let contacts = "";
      if (emp.email) contacts += `<a href="mailto:${emp.email}" aria-label="Email ${escapeHtml(emp.name)}" title="${emp.email}">✉️</a>`;
      if (emp.phone) contacts += `<a href="${telHref(emp.phone)}" aria-label="Call ${escapeHtml(emp.name)}" title="${escapeHtml(emp.phone)}">📞</a>`;

      card.innerHTML = `
        <div class="team-photo">
          <img src="${escapeHtml(imgSrc)}" alt="${escapeHtml(emp.name)}${posLabel ? ", " + escapeHtml(posLabel) : ""} at VST"
               loading="lazy" onerror="this.onerror=null;this.src='images/placeholder.svg';">
        </div>
        <div class="team-body">
          <h3 class="team-name">${escapeHtml(emp.name)}</h3>
          ${posLabel ? `<p class="team-position">${escapeHtml(posLabel)}</p>` : ""}
          <span class="team-dept">${escapeHtml(deptLabel)}</span>
          ${descLabel ? `<p class="team-desc">${escapeHtml(descLabel)}</p>` : ""}
          ${contacts ? `<div class="team-contacts">${contacts}</div>` : ""}
        </div>`;
      grid.appendChild(card);
    });
  }

  // Try to load the live team from the database (api/employees.php).
  // If it is not available (no PHP, no DB, offline), the built-in list stays.
  async function loadTeamFromApi() {
    try {
      const res = await fetch("api/employees.php", { cache: "no-store" });
      if (!res.ok) return;
      const data = await res.json();
      if (Array.isArray(data) && data.length) {
        activeEmployees = data;
        renderTeam(getActiveFilter());
      }
    } catch (err) {
      /* keep the built-in list — the site keeps working */
    }
  }

  /* ---------------- Settings (contacts + social) from database ---------------- */
  async function loadSettingsFromApi() {
    try {
      const res = await fetch("api/settings.php", { cache: "no-store" });
      if (!res.ok) return;
      const s = await res.json();
      if (!s || typeof s !== "object" || s.error) return;
      ["address", "phone", "whatsapp", "email", "hours"].forEach((k) => {
        if (k in s) CONTACT_INFO[k] = s[k] || "";
      });
      SOCIAL.instagram = s.social_instagram || "";
      SOCIAL.facebook  = s.social_facebook  || "";
      SOCIAL.tiktok    = s.social_tiktok    || "";
      SOCIAL.youtube   = s.social_youtube   || "";
      SOCIAL.linkedin  = s.social_linkedin  || "";
      SOCIAL.whatsapp  = s.social_whatsapp  || "";
      populateContact();
      populateSocial();
      refreshWhatsApp();
    } catch (err) { /* keep built-in contacts */ }
  }

  /* ---------------- Tourism (destinations + gallery) from database ---------------- */
  let activeDestinations = null;
  let activeGallery = null;

  function destLoc(d)  { return d.locations    ? (d.locations[currentLang]    || d.locations.en    || "") : ""; }
  function destDesc(d) { return d.descriptions ? (d.descriptions[currentLang] || d.descriptions.en || "") : ""; }

  function renderDestinations(list) {
    const grid = $("#destinationsGrid");
    if (!grid) return;
    grid.innerHTML = list.map((d) => {
      const img = d.image || "images/placeholder.svg";
      return `
      <article class="card destination-card">
        <div class="destination-photo lightbox-item" data-caption="${escapeHtml(d.name)}">
          <img src="${escapeHtml(img)}" alt="${escapeHtml(d.name)}" loading="lazy"
               onerror="this.onerror=null;this.src='images/placeholder.svg';">
        </div>
        <div class="destination-body">
          <h3>${escapeHtml(d.name)}</h3>
          <p class="destination-loc">📍 <span>${escapeHtml(destLoc(d))}</span></p>
          <p>${escapeHtml(destDesc(d))}</p>
          <div class="card-actions">
            <button type="button" class="btn btn-sm btn-ghost lightbox-open" data-target="${escapeHtml(img)}" data-caption="${escapeHtml(d.name)}">${escapeHtml(t("tour_view"))}</button>
            <a href="#contact" class="btn btn-sm btn-primary">${escapeHtml(t("tour_request"))}</a>
          </div>
        </div>
      </article>`;
    }).join("");
  }

  function renderGallery(list) {
    const grid = $("#galleryGrid");
    if (!grid) return;
    grid.innerHTML = list.map((g) => {
      const img = g.image || "images/placeholder.svg";
      return `
      <div class="gallery-item lightbox-item" data-caption="${escapeHtml(g.caption || "")}">
        <img src="${escapeHtml(img)}" alt="${escapeHtml(g.caption || "")}" loading="lazy"
             onerror="this.onerror=null;this.src='images/placeholder.svg';">
      </div>`;
    }).join("");
  }

  async function loadTourismFromApi() {
    try {
      const res = await fetch("api/tourism.php", { cache: "no-store" });
      if (!res.ok) return;
      const data = await res.json();
      if (data && Array.isArray(data.destinations) && data.destinations.length) {
        activeDestinations = data.destinations;
        activeGallery = Array.isArray(data.gallery) ? data.gallery : [];
        renderDestinations(activeDestinations);
        renderGallery(activeGallery);
      }
    } catch (err) { /* keep built-in tourism section */ }
  }

  function initTeamFilter() {
    const bar = $("#teamFilter");
    if (!bar) return;
    bar.addEventListener("click", (e) => {
      const btn = e.target.closest(".filter-btn");
      if (!btn) return;
      $$(".filter-btn", bar).forEach((b) => b.classList.remove("is-active"));
      btn.classList.add("is-active");
      renderTeam(btn.dataset.filter);
    });
  }

  function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, (c) =>
      ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  }

  /* ---------------- Lightbox ---------------- */
  let lbItems = [], lbIndex = 0;

  function initLightbox() {
    const lb = $("#lightbox");
    if (!lb) return;
    const img = $("#lbImage"), cap = $("#lbCaption");

    function collect() {
      // All images that live inside a .lightbox-item (galleries + portfolio + destinations)
      lbItems = $$(".lightbox-item img");
    }

    function open(target) {
      collect();
      lbIndex = Math.max(0, lbItems.indexOf(target));
      show();
      lb.hidden = false;
      document.body.style.overflow = "hidden";
    }
    function show() {
      const el = lbItems[lbIndex];
      if (!el) return;
      img.src = el.currentSrc || el.src;
      const fig = el.closest(".lightbox-item");
      img.alt = el.alt || "";
      cap.textContent = (fig && fig.getAttribute("data-caption")) || el.alt || "";
    }
    function close() { lb.hidden = true; document.body.style.overflow = ""; }
    function next(d) { lbIndex = (lbIndex + d + lbItems.length) % lbItems.length; show(); }

    // Open when any lightbox item image is clicked
    document.addEventListener("click", (e) => {
      const item = e.target.closest(".lightbox-item");
      if (item && !e.target.closest(".card-actions")) {
        const image = item.querySelector("img");
        if (image) { e.preventDefault(); open(image); }
        return;
      }
      // "View Photos" buttons in destination cards
      const opener = e.target.closest(".lightbox-open");
      if (opener) {
        e.preventDefault();
        collect();
        const src = opener.getAttribute("data-target");
        const match = lbItems.find((im) => (im.getAttribute("src") === src) || im.src.endsWith(src));
        if (match) open(match); else if (lbItems.length) open(lbItems[0]);
      }
    });

    $("#lbClose").addEventListener("click", close);
    $("#lbPrev").addEventListener("click", () => next(-1));
    $("#lbNext").addEventListener("click", () => next(1));
    lb.addEventListener("click", (e) => { if (e.target === lb) close(); });
    document.addEventListener("keydown", (e) => {
      if (lb.hidden) return;
      if (e.key === "Escape") close();
      else if (e.key === "ArrowLeft") next(-1);
      else if (e.key === "ArrowRight") next(1);
    });
  }

  /* ---------------- Header scroll + back-to-top + active nav ---------------- */
  function initScroll() {
    const header = $("#siteHeader");
    const toTop = $("#backToTop");
    const sections = $$("main section[id]");
    const navLinks = $$(".nav-list a");

    const onScroll = () => {
      const y = window.scrollY;
      if (header) header.classList.toggle("scrolled", y > 10);
      if (toTop) { toTop.hidden = y < 500 ? true : false; toTop.classList.toggle("show", y >= 500); }

      // Highlight active nav link
      let current = "";
      sections.forEach((sec) => { if (y >= sec.offsetTop - 120) current = sec.id; });
      navLinks.forEach((a) => a.classList.toggle("active", a.getAttribute("href") === "#" + current));
    };

    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();

    if (toTop) toTop.addEventListener("click", () => window.scrollTo({ top: 0, behavior: "smooth" }));
  }

  /* ---------------- Reveal on scroll ---------------- */
  function initReveal() {
    const els = $$(".reveal");
    if (!("IntersectionObserver" in window) || els.length === 0) {
      els.forEach((el) => el.classList.add("visible"));
      return;
    }
    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) { entry.target.classList.add("visible"); io.unobserve(entry.target); }
      });
    }, { threshold: 0.12, rootMargin: "0px 0px -40px 0px" });
    els.forEach((el) => io.observe(el));
  }

  /* ---------------- Contact info population ---------------- */
  function buildWaLink(number, message) {
    return "https://wa.me/" + digitsOnly(number) + "?text=" + encodeURIComponent(message);
  }

  function populateContact() {
    // Fill each [data-value] and hide its [data-field] row when empty
    const setField = (field, value, formatter) => {
      $$(`[data-field="${field}"]`).forEach((row) => {
        const holder = row.querySelector(`[data-value="${field}"]`);
        if (!value) { row.hidden = true; return; }
        row.hidden = false;
        if (holder) formatter ? formatter(holder, value) : (holder.textContent = value);
      });
    };

    setField("address", CONTACT_INFO.address);
    setField("phone", CONTACT_INFO.phone, (el, v) => {
      el.textContent = v; if (el.tagName === "A") el.href = telHref(v);
    });
    setField("whatsapp", CONTACT_INFO.whatsapp, (el, v) => {
      el.textContent = v; if (el.tagName === "A") el.href = buildWaLink(v, WA_GREETING[currentLang]);
    });
    setField("hours", CONTACT_INFO.hours);
    // email is hard-coded in HTML; only hide if explicitly cleared
    if (!CONTACT_INFO.email) $$('[data-field="email"]').forEach((r) => (r.hidden = true));

    // Big contact buttons
    const btnCall = $("#btnCall"), btnWhats = $("#btnWhats"), btnDir = $("#btnDirections"), btnEmail = $("#btnEmail");
    if (btnEmail && CONTACT_INFO.email) btnEmail.href = "mailto:" + CONTACT_INFO.email;
    if (btnCall) { if (CONTACT_INFO.phone) { btnCall.href = telHref(CONTACT_INFO.phone); btnCall.hidden = false; } else btnCall.hidden = true; }
    if (btnWhats) { if (CONTACT_INFO.whatsapp) { btnWhats.href = buildWaLink(CONTACT_INFO.whatsapp, WA_GREETING[currentLang]); btnWhats.hidden = false; } else btnWhats.hidden = true; }
    if (btnDir) {
      if (CONTACT_INFO.address) { btnDir.href = "https://www.google.com/maps/search/?api=1&query=" + encodeURIComponent(CONTACT_INFO.address); btnDir.hidden = false; }
      else btnDir.hidden = true;
    }

    renderMap();
  }

  // Build the interactive Google map. Uses MAP.query (coordinates or address),
  // falling back to the company address. If MAP.apiKey is set it uses Google's
  // official Embed API; otherwise the free keyless embed (no API key needed).
  function renderMap() {
    const box = $("#mapEmbed");
    if (!box) return;
    const M = (typeof MAP === "object" && MAP) ? MAP : {};
    const q = M.query || CONTACT_INFO.address || "";
    if (!q) {
      box.innerHTML = '<div class="map-placeholder">🗺️ ' + escapeHtml(t("contact_map")) + '</div>';
      return;
    }
    const z = M.zoom || 16;
    let src;
    if (M.apiKey) {
      src = "https://www.google.com/maps/embed/v1/place?key=" + encodeURIComponent(M.apiKey) +
            "&q=" + encodeURIComponent(q) + "&center=" + encodeURIComponent(q) +
            "&zoom=" + z + "&maptype=roadmap";
    } else {
      src = "https://maps.google.com/maps?q=" + encodeURIComponent(q) +
            "&t=&z=" + z + "&ie=UTF8&iwloc=&output=embed";
    }
    box.innerHTML =
      '<iframe title="VST location map" width="100%" height="450" style="border:0" ' +
      'loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="' + src + '"></iframe>';
  }

  /* ---------------- CEO phone / WhatsApp buttons ---------------- */
  function populateCEOs() {
    const build = (containerKey, number) => {
      const box = $(`.ceo-contacts[data-ceo="${containerKey}"]`);
      if (!box || !number) return;
      const tel = document.createElement("a");
      tel.className = "ceo-link";
      tel.href = telHref(number);
      tel.innerHTML = `<span class="ceo-link-ic">📞</span><span>${t("lead_phone")}</span>`;
      const wa = document.createElement("a");
      wa.className = "ceo-link wa";
      wa.target = "_blank"; wa.rel = "noopener";
      wa.href = buildWaLink(number, WA_GREETING[currentLang]);
      wa.innerHTML = `<span class="ceo-link-ic">💬</span><span>${t("lead_whatsapp")}</span>`;
      box.appendChild(tel);
      box.appendChild(wa);
    };
    build("dix", CEO_PHONES.dix);
    build("vanusa", CEO_PHONES.vanusa);
  }

  /* ---------------- Social links ---------------- */
  function populateSocial() {
    $$("#socialLinks a").forEach((a) => {
      const key = a.getAttribute("data-social");
      let url = SOCIAL[key] || "";
      if (key === "whatsapp" && !url && CONTACT_INFO.whatsapp) url = buildWaLink(CONTACT_INFO.whatsapp, WA_GREETING[currentLang]);
      if (url) { a.href = url; a.target = "_blank"; a.rel = "noopener"; a.hidden = false; }
      else a.hidden = true;
    });
  }

  /* ---------------- WhatsApp float + refresh on language change ---------------- */
  function refreshWhatsApp() {
    const number = CONTACT_INFO.whatsapp || "";
    const float = $("#whatsappFloat");
    if (float) {
      if (number) { float.href = buildWaLink(number, WA_GREETING[currentLang]); float.hidden = false; }
      else float.hidden = true;   // hidden until a number is provided
    }
    // Update any already-rendered wa links (contact button, contact row, social)
    const waBtn = $("#btnWhats");
    if (waBtn && number) waBtn.href = buildWaLink(number, WA_GREETING[currentLang]);
    const waRow = $('[data-value="whatsapp"]');
    if (waRow && waRow.tagName === "A" && number) waRow.href = buildWaLink(number, WA_GREETING[currentLang]);
  }

  /* ---------------- Contact form ---------------- */
  function initForm() {
    const form = $("#contactForm");
    if (!form) return;

    const showError = (id, on) => {
      const row = $("#" + id).closest(".form-row");
      if (row) row.classList.toggle("invalid", on);
    };

    form.addEventListener("submit", (e) => {
      e.preventDefault();

      const name = $("#f-name").value.trim();
      const email = $("#f-email").value.trim();
      const phone = $("#f-phone").value.trim();
      const lang = $("#f-lang").value;
      const service = $("#f-service").value;
      const message = $("#f-message").value.trim();

      const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
      let valid = true;
      if (!name) { showError("f-name", true); valid = false; } else showError("f-name", false);
      if (!emailOk) { showError("f-email", true); valid = false; } else showError("f-email", false);
      if (!message) { showError("f-message", true); valid = false; } else showError("f-message", false);
      if (!valid) return;

      // Build a readable message
      const labels = translations[currentLang];
      const body =
        `${labels.contact_title} — ${labels.form_service}: ${service}\n` +
        `${labels.form_name}: ${name}\n` +
        (phone ? `${labels.form_phone}: ${phone}\n` : "") +
        `${labels.form_email}: ${email}\n` +
        `${labels.form_lang}: ${lang}\n` +
        `${labels.form_message}: ${message}`;

      // ===== OPTION A (default): open WhatsApp with the message =====
      if (CONTACT_INFO.whatsapp) {
        window.open(buildWaLink(CONTACT_INFO.whatsapp, body), "_blank", "noopener");
        form.reset();
        return;
      }

      // ===== FALLBACK: open email client if no WhatsApp number is set =====
      // (Replace this whole block with Formspree / EmailJS if you prefer.)
      // --- Formspree example (uncomment & set your form id):
      //   fetch("https://formspree.io/f/XXXXXXX", { method:"POST",
      //     headers:{ "Accept":"application/json" }, body: new FormData(form) })
      //     .then(() => { alert("Sent!"); form.reset(); });
      //   return;
      const subject = encodeURIComponent(`VST — ${service}`);
      window.location.href = `mailto:${CONTACT_INFO.email}?subject=${subject}&body=${encodeURIComponent(body)}`;
      form.reset();
    });

    // Clear error styling as the user types
    ["f-name", "f-email", "f-message"].forEach((id) => {
      const el = $("#" + id);
      if (el) el.addEventListener("input", () => showError(id, false));
    });
  }

  /* ---------------- FAQ accordion ---------------- */
  function initFaq() {
    $$(".faq-q").forEach((btn) => {
      btn.addEventListener("click", () => {
        const item = btn.closest(".faq-item");
        const open = item.classList.toggle("open");
        btn.setAttribute("aria-expanded", String(open));
      });
    });
  }

  /* ---------------- Footer year ---------------- */
  function initYear() { const y = $("#year"); if (y) y.textContent = new Date().getFullYear(); }

  /* ---------------- Init everything ---------------- */
  document.addEventListener("DOMContentLoaded", () => {
    initYear();
    populateContact();
    populateCEOs();
    populateSocial();
    initMenu();
    initTeamFilter();
    initLightbox();
    initFaq();
    initScroll();
    initReveal();
    initForm();
    initLanguage();   // applies saved language + renders team (built-in list) + WhatsApp
    loadTeamFromApi();     // upgrade the team to live database data when available
    loadSettingsFromApi(); // upgrade contacts + social links from the database
    loadTourismFromApi();  // upgrade tourism destinations + gallery from the database
  });
})();
