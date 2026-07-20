import { createI18n } from 'vue-i18n'

export const LOCALE_STORAGE_KEY = 'impressiontrack.locale'
export const SUPPORTED_LOCALES = ['pt-BR', 'en']

const pt = {
  language: { label: 'Selecionar idioma' }, common: { close: 'Fechar mensagem', loading: 'Aguarde…', cancel: 'Cancelar', month: 'mês', year: 'ano', email: 'E-mail', password: 'Senha' },
  landing: {
    nav: { features: 'Recursos', plans: 'Planos', login: 'Entrar' },
    hero: { eyebrow: 'Analytics de anúncios, sem ruído', title: 'Veja cada impressão.', emphasis: 'Decida no momento certo.', description: 'Um pixel leve, métricas claras e acompanhamento em tempo real para campanhas que não podem esperar pelo relatório de amanhã.', start: 'Começar gratuitamente', how: 'Ver como funciona', trust1: 'Sem cartão no plano Free', trust2: 'Setup em minutos', trust3: 'Dados isolados por equipe', impressions: 'IMPRESSÕES HOJE', live: '● AO VIVO', activeCampaign: 'campanha ativa', newImpression: 'Nova impressão', now: 'São Paulo · agora' },
    features: { eyebrow: 'O ESSENCIAL, MUITO BEM FEITO', title: 'Do pixel à decisão em uma tela.', trackingTitle: 'Tracking leve', trackingText: 'Um pixel transparente por anúncio. Integre sem pesar sua página e comece a coletar em minutos.', campaignsTitle: 'Campanhas organizadas', campaignsText: 'Agrupe múltiplos anúncios, compare criativos e preserve todo o histórico da sua operação.', realtimeTitle: 'Realtime de verdade', realtimeText: 'Canais privados por workspace entregam cada nova impressão ao dashboard Premium.' },
    how: { eyebrow: 'SIMPLES POR PROJETO', title: 'Publique. Colete.\nEntenda.', step1Title: 'Crie sua campanha', step1Text: 'Organize anúncios e destinos em um workspace seguro.', step2Title: 'Instale o pixel', step2Text: 'Copie o snippet exclusivo para onde seu anúncio aparece.', step3Title: 'Acompanhe o impacto', step3Text: 'Leia tendências, navegadores e volume em um painel claro.' },
    pricing: { eyebrow: 'PLANOS TRANSPARENTES', title: 'Comece pequeno. Cresça sem trocar de ferramenta.', freeText: 'Para validar campanhas e conhecer sua audiência.', monthly: 'PREMIUM MENSAL', monthlyText: 'Flexibilidade mensal com todos os recursos Premium.', annual: 'PREMIUM ANUAL', annualText: 'Economize no ano e pague por Pix ou cartão.', best: 'MELHOR VALOR', campaigns3: '3 campanhas', campaigns20: '20 campanhas', oneAd: '1 anúncio por campanha', tenAds: '10 anúncios por campanha', static: 'Dashboard sob demanda', realtime: 'Analytics em tempo real', oneMember: '1 membro', fiveMembers: 'Até 5 membros', allPremium: 'Todos os recursos Premium', singlePayment: 'Pagamento único de R$ 9,90', pixCard: 'Pix ou cartão de crédito', twelveMonths: '12 meses de acesso', create: 'Criar workspace', chooseMonthly: 'Escolher mensal', chooseAnnual: 'Escolher anual' },
    footer: '© 2026. Analytics feito para campanhas em movimento.'
  },
  auth: { registerEyebrow: 'SEU WORKSPACE COMEÇA AQUI', loginEyebrow: 'BEM-VINDO DE VOLTA', registerTitle: 'Crie sua conta.', loginTitle: 'Entre na sua conta.', registerText: 'Três campanhas gratuitas para colocar seus dados em movimento.', loginText: 'Continue de onde sua equipe parou.', google: 'Continuar com Google', divider: 'ou use seu e-mail', name: 'Nome', namePlaceholder: 'Seu nome', workspace: 'Nome do workspace', workspacePlaceholder: 'Minha empresa', emailPlaceholder: 'voce@empresa.com', passwordPlaceholder: 'Mínimo de 8 caracteres', confirm: 'Confirmar senha', create: 'Criar workspace', login: 'Entrar', haveAccount: 'Já possui uma conta?', noAccount: 'Ainda não possui conta?', createFree: 'Criar grátis' },
  shell: { dashboard: 'Dashboard', campaigns: 'Campanhas', team: 'Equipe', billing: 'Plano e cobrança', logout: 'Sair' },
  dashboard: { eyebrow: 'VISÃO GERAL', subtitle: 'O pulso das suas campanhas em um só lugar.', realtime: '● Realtime ativo', snapshot: 'Snapshot estático', refresh: '↻ Atualizar', total: 'IMPRESSÕES TOTAIS', allCampaigns: 'em todas as campanhas', active: 'CAMPANHAS ATIVAS', available: 'de {count} disponíveis', browser: 'PRINCIPAL NAVEGADOR', impressions: '{count} impressões', noData: 'sem dados', performance: 'PERFORMANCE', byCampaign: 'Impressões por campanha', audience: 'AUDIÊNCIA', browsers: 'Navegadores', createFirst: 'Crie uma campanha para começar a medir.', noImpressions: 'Ainda não há impressões.', safe: 'AMBIENTE SEGURO', simulationTitle: 'Teste o tracking com tráfego simulado', simulationText: 'Gere impressões isoladas, sem misturar os números das campanhas reais.', generating: 'Gerando…', generate: 'Gerar 25 impressões' },
  campaigns: { eyebrow: 'GESTÃO', title: 'Campanhas', usage: '{used} de {limit} campanhas utilizadas.', new: '+ Nova campanha', name: 'Nome da campanha', namePlaceholder: 'Ex.: Lançamento de inverno', create: 'Criar campanha', simulation: 'simulação', ads: '{count} anúncio(s) · ID {id}', addAd: '+ Ad', adName: 'Nome do anúncio', destination: 'https://destino.com', add: 'Adicionar', copyPixel: 'Copiar pixel', empty: 'Nenhuma campanha criada. Seu primeiro pixel está a poucos cliques.', active: 'ativa', paused: 'pausada' },
  team: { eyebrow: 'WORKSPACE', title: 'Equipe', subtitle: 'Gerencie quem pode colaborar nas suas campanhas.', members: 'MEMBROS', slots: '{count} de {limit} vagas', invite: 'CONVIDAR PESSOA', bring: 'Traga sua equipe', inviteText: 'Convites ficam disponíveis no Premium e expiram em 7 dias.', placeholder: 'pessoa@empresa.com', send: 'Enviar convite' },
  billing: { eyebrow: 'PLANO E COBRANÇA', title: 'Seu plano', subtitle: 'Escolha a periodicidade que funciona melhor para sua equipe.', premiumText: 'Realtime e colaboração para sua equipe.', freeText: 'O essencial para validar suas primeiras campanhas.', monthly: 'PREMIUM MENSAL', annual: 'PREMIUM ANUAL', best: 'MELHOR VALOR', perMonth: '/ mês', perYear: '/ ano', monthlyText: 'Todos os recursos Premium com renovação mensal.', annualText: 'Doze meses de Premium com mais economia.', pixOrCard: 'Pagamento por Pix ou cartão', cardOnce: 'Cartão em 1x', oneMonth: '1 mês de acesso', onePayment: 'Pagamento único de {amount}', creditCard: 'Pix ou cartão de crédito', twelveMonths: '12 meses de acesso', renewMonthly: 'Renovar mensal', chooseMonthly: 'Escolher mensal', renewAnnual: 'Renovar anual', chooseAnnual: 'Escolher anual', secure: 'CHECKOUT SEGURO', premiumCycle: 'Premium {cycle}', cycleMonthly: 'Mensal', cycleAnnual: 'Anual', checkoutText: 'Escolha Pix ou cartão de crédito. Você não precisa ter conta no Mercado Pago e continuará nesta página.', upToCampaigns: 'Até 20 campanhas', upToAds: 'Até 10 ads por campanha', moreUsers: 'Mais usuários e métricas em realtime', annualPayment: 'Pagamento único por Pix ou cartão', card: 'Cartão de crédito', pix: 'Pix', loadingPayment: 'Carregando meio de pagamento…', pixGenerated: 'PIX GERADO · PLANO {cycle}', scanPix: 'Escaneie ou copie o código Pix', pixConfirmation: 'Assim que o Mercado Pago confirmar o pagamento, o Premium será ativado.', copied: 'Código copiado', copyPix: 'Copiar código Pix', campaigns: 'CAMPANHAS', notArchived: 'não arquivadas', adsPerCampaign: 'ADS POR CAMPANHA', creatives: 'criativos', members: 'MEMBROS', perWorkspace: 'por workspace', realtime: 'REALTIME', privateChannels: 'canais privados', securePayment: 'Pagamento seguro com Mercado Pago', securityNote: 'Os dados do cartão são tokenizados no navegador e nunca passam pelo servidor do Tabor Ads. O plano mensal libera 1 mês; o anual libera 12 meses após a aprovação.', sdkError: 'Não foi possível carregar o checkout seguro do Mercado Pago.', pixSuccess: 'Pix criado com sucesso.', paymentApproved: 'Pagamento {cycle} aprovado. Seu Premium já está ativo.', rejected: 'Pagamento recusado. Revise os dados ou tente outro cartão.', awaiting: 'Pagamento recebido e aguardando confirmação.', optionError: 'Não foi possível carregar uma opção de pagamento. Tente novamente.', configure: 'Configure a Public Key, o Access Token e os valores dos planos no servidor.', pixCopied: 'Código Pix copiado.' }
}

const en = {
  language: { label: 'Select language' }, common: { close: 'Close message', loading: 'Please wait…', cancel: 'Cancel', month: 'month', year: 'year', email: 'Email', password: 'Password' },
  landing: {
    nav: { features: 'Features', plans: 'Plans', login: 'Log in' },
    hero: { eyebrow: 'Ad analytics, without the noise', title: 'See every impression.', emphasis: 'Decide at the right time.', description: 'A lightweight pixel, clear metrics, and real-time monitoring for campaigns that cannot wait for tomorrow’s report.', start: 'Start for free', how: 'See how it works', trust1: 'No card required for Free', trust2: 'Set up in minutes', trust3: 'Team-isolated data', impressions: 'IMPRESSIONS TODAY', live: '● LIVE', activeCampaign: 'active campaign', newImpression: 'New impression', now: 'São Paulo · now' },
    features: { eyebrow: 'THE ESSENTIALS, DONE RIGHT', title: 'From pixel to decision on one screen.', trackingTitle: 'Lightweight tracking', trackingText: 'One transparent pixel per ad. Integrate without slowing down your page and start collecting in minutes.', campaignsTitle: 'Organized campaigns', campaignsText: 'Group multiple ads, compare creatives, and keep your entire operation history.', realtimeTitle: 'True real time', realtimeText: 'Private workspace channels deliver every new impression to the Premium dashboard.' },
    how: { eyebrow: 'SIMPLE BY DESIGN', title: 'Publish. Collect.\nUnderstand.', step1Title: 'Create your campaign', step1Text: 'Organize ads and destinations in a secure workspace.', step2Title: 'Install the pixel', step2Text: 'Copy the unique snippet to wherever your ad appears.', step3Title: 'Track the impact', step3Text: 'Read trends, browsers, and volume in a clear dashboard.' },
    pricing: { eyebrow: 'TRANSPARENT PRICING', title: 'Start small. Grow without switching tools.', freeText: 'Validate campaigns and learn about your audience.', monthly: 'MONTHLY PREMIUM', monthlyText: 'Monthly flexibility with every Premium feature.', annual: 'ANNUAL PREMIUM', annualText: 'Save throughout the year and pay with Pix or card.', best: 'BEST VALUE', campaigns3: '3 campaigns', campaigns20: '20 campaigns', oneAd: '1 ad per campaign', tenAds: '10 ads per campaign', static: 'On-demand dashboard', realtime: 'Real-time analytics', oneMember: '1 member', fiveMembers: 'Up to 5 members', allPremium: 'All Premium features', singlePayment: 'One-time payment of R$ 9.90', pixCard: 'Pix or credit card', twelveMonths: '12 months of access', create: 'Create workspace', chooseMonthly: 'Choose monthly', chooseAnnual: 'Choose annual' },
    footer: '© 2026. Analytics built for campaigns in motion.'
  },
  auth: { registerEyebrow: 'YOUR WORKSPACE STARTS HERE', loginEyebrow: 'WELCOME BACK', registerTitle: 'Create your account.', loginTitle: 'Log in to your account.', registerText: 'Three free campaigns to get your data moving.', loginText: 'Continue where your team left off.', google: 'Continue with Google', divider: 'or use your email', name: 'Name', namePlaceholder: 'Your name', workspace: 'Workspace name', workspacePlaceholder: 'My company', emailPlaceholder: 'you@company.com', passwordPlaceholder: 'At least 8 characters', confirm: 'Confirm password', create: 'Create workspace', login: 'Log in', haveAccount: 'Already have an account?', noAccount: 'Don’t have an account yet?', createFree: 'Create for free' },
  shell: { dashboard: 'Dashboard', campaigns: 'Campaigns', team: 'Team', billing: 'Plan and billing', logout: 'Log out' },
  dashboard: { eyebrow: 'OVERVIEW', subtitle: 'The pulse of your campaigns in one place.', realtime: '● Real time active', snapshot: 'Static snapshot', refresh: '↻ Refresh', total: 'TOTAL IMPRESSIONS', allCampaigns: 'across all campaigns', active: 'ACTIVE CAMPAIGNS', available: '{count} available', browser: 'TOP BROWSER', impressions: '{count} impressions', noData: 'no data', performance: 'PERFORMANCE', byCampaign: 'Impressions by campaign', audience: 'AUDIENCE', browsers: 'Browsers', createFirst: 'Create a campaign to start measuring.', noImpressions: 'No impressions yet.', safe: 'SAFE ENVIRONMENT', simulationTitle: 'Test tracking with simulated traffic', simulationText: 'Generate isolated impressions without mixing them with real campaign numbers.', generating: 'Generating…', generate: 'Generate 25 impressions' },
  campaigns: { eyebrow: 'MANAGEMENT', title: 'Campaigns', usage: '{used} of {limit} campaigns used.', new: '+ New campaign', name: 'Campaign name', namePlaceholder: 'E.g. Winter launch', create: 'Create campaign', simulation: 'simulation', ads: '{count} ad(s) · ID {id}', addAd: '+ Ad', adName: 'Ad name', destination: 'https://destination.com', add: 'Add', copyPixel: 'Copy pixel', empty: 'No campaigns created. Your first pixel is only a few clicks away.', active: 'active', paused: 'paused' },
  team: { eyebrow: 'WORKSPACE', title: 'Team', subtitle: 'Manage who can collaborate on your campaigns.', members: 'MEMBERS', slots: '{count} of {limit} seats', invite: 'INVITE SOMEONE', bring: 'Bring your team', inviteText: 'Invitations are available on Premium and expire in 7 days.', placeholder: 'person@company.com', send: 'Send invitation' },
  billing: { eyebrow: 'PLAN AND BILLING', title: 'Your plan', subtitle: 'Choose the billing cycle that works best for your team.', premiumText: 'Real time and collaboration for your team.', freeText: 'The essentials to validate your first campaigns.', monthly: 'MONTHLY PREMIUM', annual: 'ANNUAL PREMIUM', best: 'BEST VALUE', perMonth: '/ month', perYear: '/ year', monthlyText: 'Every Premium feature with monthly renewal.', annualText: 'Twelve months of Premium with greater savings.', pixOrCard: 'Pay with Pix or card', cardOnce: 'Card in one payment', oneMonth: '1 month of access', onePayment: 'One-time payment of {amount}', creditCard: 'Pix or credit card', twelveMonths: '12 months of access', renewMonthly: 'Renew monthly', chooseMonthly: 'Choose monthly', renewAnnual: 'Renew annually', chooseAnnual: 'Choose annual', secure: 'SECURE CHECKOUT', premiumCycle: 'Premium {cycle}', cycleMonthly: 'Monthly', cycleAnnual: 'Annual', checkoutText: 'Choose Pix or credit card. You do not need a Mercado Pago account and will remain on this page.', upToCampaigns: 'Up to 20 campaigns', upToAds: 'Up to 10 ads per campaign', moreUsers: 'More users and real-time metrics', annualPayment: 'One-time payment by Pix or card', card: 'Credit card', pix: 'Pix', loadingPayment: 'Loading payment method…', pixGenerated: 'PIX CREATED · {cycle} PLAN', scanPix: 'Scan or copy the Pix code', pixConfirmation: 'Premium will be activated as soon as Mercado Pago confirms the payment.', copied: 'Code copied', copyPix: 'Copy Pix code', campaigns: 'CAMPAIGNS', notArchived: 'not archived', adsPerCampaign: 'ADS PER CAMPAIGN', creatives: 'creatives', members: 'MEMBERS', perWorkspace: 'per workspace', realtime: 'REAL TIME', privateChannels: 'private channels', securePayment: 'Secure payments with Mercado Pago', securityNote: 'Card data is tokenized in the browser and never passes through the Tabor Ads server. The monthly plan grants 1 month; the annual plan grants 12 months after approval.', sdkError: 'Could not load Mercado Pago secure checkout.', pixSuccess: 'Pix created successfully.', paymentApproved: '{cycle} payment approved. Your Premium plan is now active.', rejected: 'Payment declined. Check the details or try another card.', awaiting: 'Payment received and awaiting confirmation.', optionError: 'Could not load a payment option. Please try again.', configure: 'Configure the Public Key, Access Token, and plan prices on the server.', pixCopied: 'Pix code copied.' }
}

pt.billing.securityNote = 'Os dados do cartão são tokenizados no navegador e nunca passam pelo servidor do Tabor Ads. O plano mensal libera 1 mês; o anual libera 12 meses após a aprovação.'
en.billing.securityNote = 'Card data is tokenized in the browser and never passes through the Tabor Ads server. The monthly plan grants 1 month; the annual plan grants 12 months after approval.'
pt.auth.emailPlaceholder = "voce{'@'}empresa.com"
pt.team.placeholder = "pessoa{'@'}empresa.com"
en.auth.emailPlaceholder = "you{'@'}company.com"
en.team.placeholder = "person{'@'}company.com"

Object.assign(pt.dashboard, {
  filtersEyebrow: 'ANÁLISE PERSONALIZADA',
  filtersTitle: 'Filtros do dashboard',
  filtersSubtitle: 'Combine campanhas, anúncios e período para explorar os resultados.',
  period: 'Período',
  lastDays: 'Últimos {count} dias',
  resetFilters: 'Limpar filtros',
  campaignFilter: 'Campanhas',
  adFilter: 'Anúncios',
  selectedCount: '{count} selecionados',
  allSelectedHint: 'Nenhuma seleção inclui todas',
  allAdsHint: 'Opcional · todos os anúncios incluídos',
  noCampaignOptions: 'Nenhuma campanha disponível.',
  noAdOptions: 'As campanhas selecionadas ainda não possuem anúncios.',
  filteredScope: 'no recorte selecionado',
  periodTrend: 'TENDÊNCIA DO PERÍODO',
  comparedHalves: 'segunda metade vs. primeira metade',
  timelineEyebrow: 'EVOLUÇÃO DIÁRIA',
  timelineTitle: 'Crescimento de impressões ao longo do tempo',
  dailyImpressions: 'Impressões por dia',
  trend: { growth: 'Em crescimento', decline: 'Em diminuição', stable: 'Estável' },
  simulationLive: 'DEMO EM TEMPO REAL',
  simulationText: 'A cada segundo, todas as campanhas simuladas recebem tráfego com pesos diferentes e atualizam os gráficos em tempo real.',
  simulationCampaign: 'Campanha de simulação',
  generatingLive: 'Gerando em tempo real…',
  createSimulationFirst: 'Cadastre ao menos uma campanha do tipo simulação para usar o modo simulador.',
  goToCampaigns: 'Criar campanha',
  simulationOn: 'Simulação ligada',
  simulationOff: 'Simulação desligada',
  simulationLimit: 'Limite contínuo de 3 minutos',
  timeRemaining: '{time} restantes',
  turnSimulationOn: 'Ligar geração de tráfego simulado',
  turnSimulationOff: 'Desligar geração de tráfego simulado',
  turnOn: 'Ligar',
  turnOff: 'Desligar',
})
Object.assign(en.dashboard, {
  filtersEyebrow: 'CUSTOM ANALYSIS',
  filtersTitle: 'Dashboard filters',
  filtersSubtitle: 'Combine campaigns, ads, and a date range to explore the results.',
  period: 'Date range',
  lastDays: 'Last {count} days',
  resetFilters: 'Clear filters',
  campaignFilter: 'Campaigns',
  adFilter: 'Ads',
  selectedCount: '{count} selected',
  allSelectedHint: 'No selection includes all campaigns',
  allAdsHint: 'Optional · all ads included',
  noCampaignOptions: 'No campaigns available.',
  noAdOptions: 'The selected campaigns do not have any ads yet.',
  filteredScope: 'within the selected scope',
  periodTrend: 'PERIOD TREND',
  comparedHalves: 'second half vs. first half',
  timelineEyebrow: 'DAILY EVOLUTION',
  timelineTitle: 'Impression growth over time',
  dailyImpressions: 'Daily impressions',
  trend: { growth: 'Growing', decline: 'Declining', stable: 'Stable' },
  simulationLive: 'LIVE DEMO',
  simulationText: 'Every second, all simulation campaigns receive traffic with different weights and update the charts in real time.',
  simulationCampaign: 'Simulation campaign',
  generatingLive: 'Generating in real time…',
  createSimulationFirst: 'Create at least one simulation campaign to use simulator mode.',
  goToCampaigns: 'Create campaign',
  simulationOn: 'Simulation on',
  simulationOff: 'Simulation off',
  simulationLimit: '3-minute continuous limit',
  timeRemaining: '{time} remaining',
  turnSimulationOn: 'Turn simulated traffic generation on',
  turnSimulationOff: 'Turn simulated traffic generation off',
  turnOn: 'Turn on',
  turnOff: 'Turn off',
})
Object.assign(pt.campaigns, {
  simulationMode: 'Modo simulação',
  simulationUnlimited: 'Não consome o limite do plano e habilita a demonstração em tempo real.',
  outsideLimit: 'Fora do limite',
})
Object.assign(en.campaigns, {
  simulationMode: 'Simulation mode',
  simulationUnlimited: 'Does not count toward the plan limit and enables the real-time demo.',
  outsideLimit: 'Outside plan limit',
})
Object.assign(pt.common, {
  copyError: 'Não foi possível copiar o conteúdo.',
})
Object.assign(en.common, {
  copyError: 'Could not copy the content.',
})
Object.assign(pt.auth, {
  loginSuccess: 'Login realizado com sucesso.',
  registerSuccess: 'Conta criada com sucesso.',
})
Object.assign(en.auth, {
  loginSuccess: 'Logged in successfully.',
  registerSuccess: 'Account created successfully.',
})
Object.assign(pt.shell, {
  logoutSuccess: 'Sessão encerrada com sucesso.',
})
Object.assign(en.shell, {
  logoutSuccess: 'Logged out successfully.',
})
Object.assign(pt.campaigns, {
  created: 'Campanha criada com sucesso.',
  adCreated: 'Anúncio adicionado com sucesso.',
  pixelCopied: 'Pixel copiado com sucesso.',
})
Object.assign(en.campaigns, {
  created: 'Campaign created successfully.',
  adCreated: 'Ad added successfully.',
  pixelCopied: 'Pixel copied successfully.',
})
Object.assign(pt.team, {
  invitationSent: 'Convite enviado com sucesso.',
})
Object.assign(en.team, {
  invitationSent: 'Invitation sent successfully.',
})
Object.assign(pt.billing, {
  generatingPix: 'Gerando QR Code Pix…',
  pixUnavailable: 'O Mercado Pago não retornou o QR Code Pix. Tente novamente.',
})
Object.assign(en.billing, {
  generatingPix: 'Generating Pix QR Code…',
  pixUnavailable: 'Mercado Pago did not return the Pix QR Code. Please try again.',
})

Object.assign(pt.common, { enabled: 'Ativo', disabled: 'Inativo' })
Object.assign(en.common, { enabled: 'Enabled', disabled: 'Disabled' })
Object.assign(pt.shell, { profile: 'Perfil e workspace' })
Object.assign(en.shell, { profile: 'Profile and workspace' })
pt.profile = {
  eyebrow: 'CONTA E WORKSPACE', title: 'Seu perfil', subtitle: 'Acompanhe seu plano, os limites do workspace e a segurança da sua conta.',
  currentPlan: 'PLANO ATUAL', premium: 'Premium', free: 'Free', premiumDescription: 'Recursos avançados, colaboração e métricas em tempo real.', freeDescription: 'O essencial para validar suas primeiras campanhas.',
  expiresAt: 'VÁLIDO ATÉ', notApplicable: 'Não se aplica', noExpiration: 'Sem expiração cadastrada', managePlan: 'Gerenciar plano', viewPlans: 'Conhecer Premium',
  workspace: 'WORKSPACE', identifier: 'Identificador', createdAt: 'Criado em', memberSince: 'Membro desde', realtime: 'Métricas em realtime',
  campaignUsage: 'CAMPANHAS', standardCampaigns: 'campanhas padrão', adsLimit: 'ADS', perCampaign: 'por campanha', memberUsage: 'MEMBROS', workspaceSeats: 'vagas do workspace', simulations: 'SIMULAÇÕES', outsideLimit: 'fora do limite do plano',
  account: 'USUÁRIO', personalInfo: 'Informações da conta', name: 'Nome', verified: 'Verificado', notVerified: 'Não verificado', accountSince: 'Conta criada em', language: 'Idioma preferido', loginMethods: 'Formas de acesso', emailPassword: 'E-mail e senha', noLoginMethod: 'Não informado',
  security: 'SEGURANÇA', changePassword: 'Trocar senha', createPassword: 'Criar uma senha', changePasswordText: 'Confirme sua senha atual antes de definir uma nova.', createPasswordText: 'Sua conta usa login externo. Crie uma senha para também poder entrar por e-mail.', currentPassword: 'Senha atual', newPassword: 'Nova senha', confirmPassword: 'Confirmar nova senha', passwordHint: 'Use ao menos 8 caracteres, com letras e números.', savePassword: 'Salvar nova senha',
  roles: { owner: 'Proprietário', admin: 'Administrador', member: 'Membro' }, cycles: { monthly: 'Mensal', annual: 'Anual', premium: 'Premium' }, statuses: { active: 'Ativo', past_due: 'Em carência', pending: 'Pendente', canceled: 'Cancelado' },
}
en.profile = {
  eyebrow: 'ACCOUNT AND WORKSPACE', title: 'Your profile', subtitle: 'Review your plan, workspace limits, and account security.',
  currentPlan: 'CURRENT PLAN', premium: 'Premium', free: 'Free', premiumDescription: 'Advanced features, collaboration, and real-time metrics.', freeDescription: 'The essentials to validate your first campaigns.',
  expiresAt: 'VALID UNTIL', notApplicable: 'Not applicable', noExpiration: 'No expiration registered', managePlan: 'Manage plan', viewPlans: 'Explore Premium',
  workspace: 'WORKSPACE', identifier: 'Identifier', createdAt: 'Created on', memberSince: 'Member since', realtime: 'Real-time metrics',
  campaignUsage: 'CAMPAIGNS', standardCampaigns: 'standard campaigns', adsLimit: 'ADS', perCampaign: 'per campaign', memberUsage: 'MEMBERS', workspaceSeats: 'workspace seats', simulations: 'SIMULATIONS', outsideLimit: 'outside the plan limit',
  account: 'USER', personalInfo: 'Account information', name: 'Name', verified: 'Verified', notVerified: 'Not verified', accountSince: 'Account created on', language: 'Preferred language', loginMethods: 'Sign-in methods', emailPassword: 'Email and password', noLoginMethod: 'Not provided',
  security: 'SECURITY', changePassword: 'Change password', createPassword: 'Create a password', changePasswordText: 'Confirm your current password before setting a new one.', createPasswordText: 'Your account uses external sign-in. Create a password to also sign in by email.', currentPassword: 'Current password', newPassword: 'New password', confirmPassword: 'Confirm new password', passwordHint: 'Use at least 8 characters, including letters and numbers.', savePassword: 'Save new password',
  roles: { owner: 'Owner', admin: 'Administrator', member: 'Member' }, cycles: { monthly: 'Monthly', annual: 'Annual', premium: 'Premium' }, statuses: { active: 'Active', past_due: 'Grace period', pending: 'Pending', canceled: 'Canceled' },
}

Object.assign(pt.billing, {
  pixCanceled: 'O pagamento Pix foi cancelado ou expirou. Gere um novo código para tentar novamente.',
  confirmationDelayed: 'A confirmação está demorando mais que o esperado. Você pode manter esta tela aberta; o plano será atualizado quando o pagamento for confirmado.',
})
Object.assign(en.billing, {
  pixCanceled: 'The Pix payment was canceled or expired. Create a new code to try again.',
  confirmationDelayed: 'Confirmation is taking longer than expected. You can keep this page open; the plan will update when the payment is confirmed.',
})

Object.assign(pt.shell, { member: 'membro' })
Object.assign(en.shell, { member: 'member' })
Object.assign(pt.team, {
  person: 'Pessoa',
  createCampaigns: 'Criar campanhas',
  viewMetrics: 'Visualizar métricas',
  permissionsUpdated: 'Permissões atualizadas com sucesso.',
  sending: 'Enviando…',
  pendingConfirmation: 'Aguardando confirmação',
  expiresAt: 'expira em {date}',
  namePlaceholder: 'Nome (opcional)',
  actions: 'Ações',
  remove: 'Excluir',
  removing: 'Excluindo…',
  protected: 'Protegido',
  cancelInvitation: 'Cancelar',
  removeConfirm: 'Excluir {name} deste workspace?',
  cancelConfirm: 'Cancelar o convite de {name}?',
  memberRemoved: 'Membro removido com sucesso.',
  invitationCanceled: 'Convite cancelado com sucesso.',
})
Object.assign(en.team, {
  person: 'Person',
  createCampaigns: 'Create campaigns',
  viewMetrics: 'View metrics',
  permissionsUpdated: 'Permissions updated successfully.',
  sending: 'Sending…',
  pendingConfirmation: 'Awaiting confirmation',
  expiresAt: 'expires on {date}',
  namePlaceholder: 'Name (optional)',
  actions: 'Actions',
  remove: 'Remove',
  removing: 'Removing…',
  protected: 'Protected',
  cancelInvitation: 'Cancel',
  removeConfirm: 'Remove {name} from this workspace?',
  cancelConfirm: 'Cancel the invitation for {name}?',
  memberRemoved: 'Member removed successfully.',
  invitationCanceled: 'Invitation canceled successfully.',
})
Object.assign(pt.profile, { createCampaigns: 'Criação de campanhas', viewMetrics: 'Visualização de métricas' })
Object.assign(en.profile, { createCampaigns: 'Campaign creation', viewMetrics: 'Metrics access' })
Object.assign(pt.dashboard, {
  workspaceOverview: 'VISÃO DO WORKSPACE',
  restrictedTitle: 'Resumo operacional',
  restrictedText: 'Seu acesso não inclui métricas de desempenho. Você ainda pode acompanhar a estrutura ativa do workspace.',
  activeCampaignSummary: 'campanhas ativas no workspace',
  adsTotal: 'ADS CADASTRADOS',
  adsSummary: 'ads ativos ou disponíveis',
})
Object.assign(en.dashboard, {
  workspaceOverview: 'WORKSPACE OVERVIEW',
  restrictedTitle: 'Operational summary',
  restrictedText: 'Your access does not include performance metrics. You can still review the active workspace structure.',
  activeCampaignSummary: 'active campaigns in the workspace',
  adsTotal: 'REGISTERED ADS',
  adsSummary: 'active or available ads',
})
pt.verification = {
  eyebrow: 'CONFIRME SEU E-MAIL',
  title: 'Verifique sua caixa de entrada.',
  text: 'Enviamos um link de verificação para {email}. As ferramentas do workspace serão liberadas após a confirmação.',
  resend: 'Reenviar e-mail de verificação',
  useAnotherAccount: 'Entrar com outra conta',
}
en.verification = {
  eyebrow: 'CONFIRM YOUR EMAIL',
  title: 'Check your inbox.',
  text: 'We sent a verification link to {email}. Workspace tools will be enabled after confirmation.',
  resend: 'Resend verification email',
  useAnotherAccount: 'Use another account',
}
pt.invitation = {
  eyebrow: 'CONVITE PARA EQUIPE',
  title: 'Entre no workspace {workspace}.',
  text: 'Este convite foi enviado para {email}. Crie sua conta ou entre para continuar.',
  createCampaigns: 'Permissão para criar campanhas',
  viewMetrics: 'Permissão para visualizar métricas',
  accept: 'Aceitar convite',
  createAndAccept: 'Criar conta e aceitar',
  loginAndAccept: 'Entrar e aceitar',
  accepted: 'Convite aceito. Bem-vindo ao workspace!',
  invalidTitle: 'Convite indisponível',
  invalidText: 'Este convite não existe, expirou ou já foi utilizado.',
}
en.invitation = {
  eyebrow: 'TEAM INVITATION',
  title: 'Join the {workspace} workspace.',
  text: 'This invitation was sent to {email}. Create your account or sign in to continue.',
  createCampaigns: 'Permission to create campaigns',
  viewMetrics: 'Permission to view metrics',
  accept: 'Accept invitation',
  createAndAccept: 'Create account and accept',
  loginAndAccept: 'Sign in and accept',
  accepted: 'Invitation accepted. Welcome to the workspace!',
  invalidTitle: 'Invitation unavailable',
  invalidText: 'This invitation does not exist, has expired, or has already been used.',
}

pt.legal = {
  footerNavigation: 'Links legais',
  back: 'Voltar para o início',
  updatedAt: 'Última atualização: 20 de julho de 2026',
  contactTitle: 'Contato',
  contactText: 'Para dúvidas, solicitações sobre dados pessoais ou assuntos relacionados a estes documentos, entre em contato:',
  privacy: {
    link: 'Política de Privacidade',
    eyebrow: 'PRIVACIDADE E PROTEÇÃO DE DADOS',
    title: 'Política de Privacidade',
    intro: 'Esta política explica como o Tabor Ads coleta, utiliza, compartilha e protege dados pessoais durante o uso do site, da plataforma de analytics e das integrações de autenticação e pagamento.',
    sections: {
      controller: {
        title: '1. Controlador e abrangência',
        text: 'O Tabor Ads é responsável pelo tratamento dos dados pessoais descritos nesta política. Ela se aplica ao site tabor-ads.dirrini.tech, às contas de usuário, aos workspaces e aos recursos de tracking, analytics, equipes e cobrança oferecidos pela plataforma.',
      },
      data: {
        title: '2. Dados que tratamos',
        text: 'Podemos tratar nome, e-mail, senha protegida por hash, idioma, situação da verificação de e-mail e vínculo com workspaces; informações de campanhas, anúncios e permissões da equipe; identificadores de sessão, endereço IP e user-agent; e dados técnicos de impressões, como horário, navegador e plataforma.\n\nPara cobrança, armazenamos identificadores, situação e histórico da transação fornecidos pelo Mercado Pago. Os dados completos do cartão são tokenizados pelo Mercado Pago e não são armazenados nos servidores do Tabor Ads.',
      },
      google: {
        title: '3. Login com Google',
        text: 'Ao escolher “Continuar com Google”, recebemos somente as informações básicas autorizadas para autenticação, como identificador da conta Google, nome, endereço de e-mail e, quando disponível, foto do perfil. Usamos esses dados exclusivamente para criar ou associar sua conta, confirmar sua identidade e permitir o acesso ao Tabor Ads.\n\nNão acessamos sua senha do Google, contatos, arquivos do Google Drive ou conteúdo de outros produtos Google. Não vendemos os dados recebidos do Google nem os utilizamos para publicidade. Você pode remover o acesso nas configurações de segurança da sua Conta Google.',
      },
      purposes: {
        title: '4. Finalidades e bases legais',
        text: 'Usamos os dados para criar e proteger contas; autenticar usuários; verificar e-mails; administrar workspaces e permissões; processar convites; registrar e apresentar métricas; detectar abuso e falhas; prestar suporte; processar pagamentos; cumprir obrigações legais; e melhorar a segurança e o funcionamento da plataforma. O tratamento se fundamenta, conforme o caso, na execução do contrato, em procedimentos preliminares, no legítimo interesse, no cumprimento de obrigação legal e no consentimento.',
      },
      sharing: {
        title: '5. Compartilhamento e operadores',
        text: 'Compartilhamos apenas o necessário com fornecedores que viabilizam o serviço, como infraestrutura de hospedagem, envio de e-mails, Google para autenticação e Mercado Pago para pagamentos. Esses fornecedores tratam dados conforme seus próprios termos e políticas. Também poderemos compartilhar informações quando exigido por lei, ordem judicial ou para proteger direitos e a segurança da plataforma e de seus usuários.',
      },
      retention: {
        title: '6. Retenção e exclusão',
        text: 'Mantemos os dados enquanto a conta ou o workspace estiver ativo e pelo período necessário para cumprir as finalidades informadas, resolver disputas, prevenir fraudes e atender obrigações legais. Após o término do tratamento, os dados poderão ser eliminados ou anonimizados, salvo quando sua conservação for permitida ou exigida por lei.',
      },
      security: {
        title: '7. Segurança',
        text: 'Adotamos controles técnicos e organizacionais para reduzir riscos de acesso não autorizado, perda, alteração ou divulgação, incluindo conexões HTTPS, isolamento por workspace, controle de permissões e proteção de credenciais. Nenhum sistema é totalmente imune a incidentes; caso ocorra um evento relevante, adotaremos as providências aplicáveis.',
      },
      rights: {
        title: '8. Seus direitos pela LGPD',
        text: 'Nos termos da Lei nº 13.709/2018, você pode solicitar confirmação e acesso aos dados, correção, anonimização, bloqueio ou eliminação de dados desnecessários, portabilidade quando aplicável, informações sobre compartilhamento, revogação do consentimento e revisão de decisões automatizadas. Para exercer seus direitos, use o contato ao final desta página. Poderemos solicitar informações para confirmar sua identidade.',
      },
      changes: {
        title: '9. Alterações desta política',
        text: 'Esta política poderá ser atualizada para refletir mudanças legais, técnicas ou nos recursos do Tabor Ads. A data da versão vigente será indicada no início da página e, quando a alteração for relevante, poderemos comunicar os usuários pelos canais disponíveis.',
      },
    },
  },
  terms: {
    link: 'Termos de Uso',
    eyebrow: 'REGRAS DE UTILIZAÇÃO',
    title: 'Termos de Uso',
    intro: 'Estes termos regulam o acesso e o uso do Tabor Ads. Ao criar uma conta ou utilizar a plataforma, você declara que leu e concordou com estas condições e com a Política de Privacidade.',
    sections: {
      acceptance: {
        title: '1. Aceitação e elegibilidade',
        text: 'Você deve possuir capacidade legal para contratar e fornecer informações verdadeiras e atualizadas. Se usar o Tabor Ads em nome de uma empresa ou organização, declara possuir autorização para vinculá-la a estes termos.',
      },
      service: {
        title: '2. O serviço',
        text: 'O Tabor Ads oferece criação de workspaces, campanhas e anúncios, geração de pixels de tracking, dashboards de analytics, simulação de tráfego, colaboração em equipe e recursos premium. As funcionalidades e limites disponíveis dependem do plano contratado e podem evoluir ao longo do tempo.',
      },
      account: {
        title: '3. Conta e segurança',
        text: 'Você é responsável por manter suas credenciais seguras, pelas atividades realizadas em sua conta e pelas permissões concedidas aos membros do workspace. Informe-nos sobre qualquer uso não autorizado. Contas criadas por e-mail precisam ser verificadas para liberar as funcionalidades protegidas. O login com Google está sujeito também aos termos da Conta Google.',
      },
      tracking: {
        title: '4. Pixels, campanhas e responsabilidade sobre dados',
        text: 'Você deve instalar e utilizar os pixels somente em propriedades e campanhas para as quais possua autorização. É sua responsabilidade fornecer avisos de privacidade, obter consentimentos quando necessários e cumprir a legislação aplicável ao coletar dados de visitantes. É proibido usar o serviço para vigilância ilícita, coleta de dados sensíveis sem base legal ou identificação indevida de pessoas.',
      },
      plans: {
        title: '5. Planos e pagamentos',
        text: 'O plano gratuito e os planos Premium possuem os limites e preços exibidos na plataforma. Pagamentos são processados pelo Mercado Pago. A ativação do Premium depende da aprovação do pagamento, e sua vigência segue o ciclo escolhido. Preços ou condições futuras poderão mudar mediante informação prévia, sem reduzir o período já pago.',
      },
      acceptableUse: {
        title: '6. Uso aceitável',
        text: 'Você não pode violar leis ou direitos de terceiros; tentar acessar contas ou dados sem autorização; contornar limites do plano; interferir na disponibilidade ou segurança do serviço; enviar código malicioso; gerar tráfego abusivo fora das ferramentas permitidas; revender o serviço sem autorização; ou utilizá-lo para fraude, spam ou atividades enganosas.',
      },
      intellectualProperty: {
        title: '7. Propriedade intelectual',
        text: 'O Tabor Ads e seus componentes, marca, interface e software são protegidos pela legislação aplicável. Estes termos concedem somente uma licença limitada, revogável e não exclusiva para uso da plataforma. Você mantém os direitos sobre os dados e conteúdos inseridos e nos concede a autorização necessária para processá-los e prestar o serviço.',
      },
      availability: {
        title: '8. Disponibilidade e limitações',
        text: 'Buscamos manter a plataforma segura e disponível, mas não garantimos operação ininterrupta ou ausência total de erros. Poderão ocorrer manutenções, indisponibilidades de fornecedores e eventos fora do nosso controle. Métricas de tracking podem sofrer variações por bloqueadores, navegadores, redes e configurações externas e não constituem garantia de desempenho publicitário.',
      },
      termination: {
        title: '9. Suspensão e encerramento',
        text: 'Você pode deixar de usar o serviço a qualquer momento. Poderemos suspender ou encerrar acessos em caso de violação destes termos, risco de segurança, fraude, inadimplência ou obrigação legal, observando a legislação aplicável. Solicitações relacionadas à exclusão de dados podem ser feitas pelo contato indicado abaixo.',
      },
      law: {
        title: '10. Legislação aplicável',
        text: 'Estes termos são regidos pelas leis da República Federativa do Brasil, inclusive pelo Marco Civil da Internet, pelo Código de Defesa do Consumidor quando aplicável e pela Lei Geral de Proteção de Dados. Eventuais conflitos serão tratados no foro competente definido pela legislação brasileira.',
      },
      changes: {
        title: '11. Alterações dos termos',
        text: 'Podemos atualizar estes termos para acompanhar mudanças no serviço ou na legislação. Alterações relevantes serão comunicadas pelos meios disponíveis e passarão a valer na data informada. A continuidade do uso após a vigência representa aceitação da nova versão.',
      },
    },
  },
}

en.legal = {
  footerNavigation: 'Legal links',
  back: 'Back to home',
  updatedAt: 'Last updated: July 20, 2026',
  contactTitle: 'Contact',
  contactText: 'For questions, personal data requests, or matters related to these documents, contact us at:',
  privacy: {
    link: 'Privacy Policy',
    eyebrow: 'PRIVACY AND DATA PROTECTION',
    title: 'Privacy Policy',
    intro: 'This policy explains how Tabor Ads collects, uses, shares, and protects personal data when you use our website, analytics platform, authentication integrations, and payment features.',
    sections: {
      controller: { title: '1. Controller and scope', text: 'Tabor Ads is responsible for the processing described in this policy. It applies to tabor-ads.dirrini.tech, user accounts, workspaces, and the tracking, analytics, team, and billing features offered by the platform.' },
      data: { title: '2. Data we process', text: 'We may process your name, email address, hashed password, language, email verification status, and workspace membership; campaign, ad, and team permission information; session identifiers, IP address, and user agent; and technical impression data such as time, browser, and platform.\n\nFor billing, we store transaction identifiers, status, and history provided by Mercado Pago. Full card details are tokenized by Mercado Pago and are not stored on Tabor Ads servers.' },
      google: { title: '3. Sign in with Google', text: 'When you choose “Continue with Google,” we receive only the basic information authorized for authentication, such as your Google account identifier, name, email address, and profile picture when available. We use it solely to create or link your account, confirm your identity, and provide access to Tabor Ads.\n\nWe do not access your Google password, contacts, Google Drive files, or content from other Google products. We do not sell data received from Google or use it for advertising. You may remove access through your Google Account security settings.' },
      purposes: { title: '4. Purposes and legal bases', text: 'We use data to create and protect accounts; authenticate users; verify email addresses; manage workspaces and permissions; process invitations; record and display metrics; detect abuse and failures; provide support; process payments; meet legal obligations; and improve platform security and operation. Depending on the context, processing relies on contract performance, pre-contractual steps, legitimate interests, legal obligations, or consent.' },
      sharing: { title: '5. Sharing and service providers', text: 'We share only what is necessary with providers that enable the service, including hosting infrastructure, email delivery, Google for authentication, and Mercado Pago for payments. These providers process data under their own terms and policies. We may also disclose information when required by law or court order, or to protect the rights and safety of the platform and its users.' },
      retention: { title: '6. Retention and deletion', text: 'We retain data while an account or workspace remains active and for as long as needed to fulfill the purposes described, resolve disputes, prevent fraud, and comply with legal obligations. Once processing ends, data may be deleted or anonymized unless retention is permitted or required by law.' },
      security: { title: '7. Security', text: 'We use technical and organizational controls intended to reduce the risk of unauthorized access, loss, alteration, or disclosure, including HTTPS connections, workspace isolation, permission controls, and credential protection. No system is completely immune to incidents; if a relevant event occurs, we will take the measures required under applicable law.' },
      rights: { title: '8. Your data protection rights', text: 'Subject to applicable law, including Brazil’s LGPD, you may request confirmation and access, correction, anonymization, restriction or deletion of unnecessary data, portability where applicable, information about sharing, withdrawal of consent, and review of automated decisions. Use the contact below to exercise these rights. We may request information to verify your identity.' },
      changes: { title: '9. Changes to this policy', text: 'We may update this policy to reflect legal, technical, or product changes. The current version date will appear at the top of this page and, when a change is material, we may notify users through available channels.' },
    },
  },
  terms: {
    link: 'Terms of Use',
    eyebrow: 'RULES OF USE',
    title: 'Terms of Use',
    intro: 'These terms govern access to and use of Tabor Ads. By creating an account or using the platform, you acknowledge that you have read and agreed to these terms and the Privacy Policy.',
    sections: {
      acceptance: { title: '1. Acceptance and eligibility', text: 'You must have legal capacity to enter into an agreement and must provide accurate, current information. If you use Tabor Ads for a company or organization, you represent that you are authorized to bind it to these terms.' },
      service: { title: '2. The service', text: 'Tabor Ads provides workspaces, campaigns and ads, tracking pixels, analytics dashboards, traffic simulation, team collaboration, and Premium features. Available functionality and limits depend on the selected plan and may evolve over time.' },
      account: { title: '3. Account and security', text: 'You are responsible for keeping credentials secure, for activity under your account, and for permissions granted to workspace members. Notify us of unauthorized use. Email accounts must be verified before protected features become available. Google sign-in is also subject to the terms governing your Google Account.' },
      tracking: { title: '4. Pixels, campaigns, and data responsibility', text: 'You must install and use pixels only on properties and campaigns you are authorized to manage. You are responsible for providing privacy notices, obtaining consent where required, and complying with applicable law when collecting visitor data. The service may not be used for unlawful surveillance, collection of sensitive data without a legal basis, or improper identification of individuals.' },
      plans: { title: '5. Plans and payments', text: 'The Free and Premium plans have the limits and prices displayed in the platform. Payments are processed by Mercado Pago. Premium activation depends on payment approval and lasts for the selected cycle. Future prices or conditions may change with prior notice without reducing any already-paid period.' },
      acceptableUse: { title: '6. Acceptable use', text: 'You may not violate laws or third-party rights; access accounts or data without permission; circumvent plan limits; disrupt service availability or security; distribute malicious code; generate abusive traffic outside permitted tools; resell the service without authorization; or use it for fraud, spam, or deceptive activity.' },
      intellectualProperty: { title: '7. Intellectual property', text: 'Tabor Ads and its software, interface, components, and brand are protected by applicable law. These terms grant only a limited, revocable, non-exclusive license to use the platform. You retain rights to data and content you submit and authorize us to process them as necessary to provide the service.' },
      availability: { title: '8. Availability and limitations', text: 'We work to keep the platform secure and available but do not guarantee uninterrupted or error-free operation. Maintenance, provider outages, and events outside our control may occur. Tracking metrics may vary due to blockers, browsers, networks, and external settings and are not a guarantee of advertising performance.' },
      termination: { title: '9. Suspension and termination', text: 'You may stop using the service at any time. We may suspend or terminate access for a violation of these terms, security risk, fraud, non-payment, or legal requirement, subject to applicable law. Data deletion requests may be submitted using the contact below.' },
      law: { title: '10. Governing law', text: 'These terms are governed by the laws of the Federative Republic of Brazil, including the Brazilian Internet Act, consumer law where applicable, and the General Data Protection Law. Disputes will be handled by the competent venue determined under Brazilian law.' },
      changes: { title: '11. Changes to these terms', text: 'We may update these terms to reflect service or legal changes. Material changes will be communicated through available channels and will take effect on the stated date. Continued use after that date constitutes acceptance of the updated version.' },
    },
  },
}

const normalize = (locale) => SUPPORTED_LOCALES.includes(locale) ? locale : 'pt-BR'
const initialLocale = normalize(localStorage.getItem(LOCALE_STORAGE_KEY))
export const i18n = createI18n({ legacy: false, locale: initialLocale, fallbackLocale: 'pt-BR', messages: { 'pt-BR': pt, en } })
export function setAppLocale(locale) { const value = normalize(locale); i18n.global.locale.value = value; localStorage.setItem(LOCALE_STORAGE_KEY, value); document.documentElement.lang = value; return value }
setAppLocale(initialLocale)
