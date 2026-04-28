/**
 * CONFIGURAZIONE ASPETTO GRAFICO (app.config.ts)
 *
 * Questo file personalizza l'aspetto dei componenti della libreria Nuxt UI.
 * Nuxt UI fornisce componenti pronti all'uso (accordion, modal, tabs, breadcrumb, ecc.)
 * e qui si modificano colori, dimensioni e stili per adattarli al design di SpediamoFacile.
 *
 * Componenti personalizzati:
 *
 * - accordion: la sezione a "fisarmonica" usata nella pagina FAQ e servizi,
 *   dove si clicca un titolo per espandere/comprimere il contenuto
 *
 * - modal: la finestra che si apre sopra la pagina (usata per dettagli servizi
 *   nel secondo step del preventivo). Ha sfondo grigio, bordi arrotondati
 *   e un pulsante X per chiuderla
 *
 * - tabs: le linguette per cambiare tra diverse viste (es. nelle pagine account)
 *   con colore primario teal (#005961)
 *
 * - breadcrumb: il percorso di navigazione (es. Home > Servizi > Pagamento)
 *   con link neri e pagina attiva in teal
 *
 * - user: il componente che mostra nome e avatar dell'utente
 *   (usato nelle recensioni)
 */
export default defineAppConfig({
	// LEGAL-TODO: dati reali da inserire prima di go-live (responsabilità utente).
	// Grep marker [INSERIRE_ per trovare tutti i punti da completare.
	// Riferimenti normativi: DPR 633/72 art. 35 (P.IVA), d.lgs 196/2003 + 101/2018
	// (Codice Privacy it.), GDPR Art. 30 (Registro Trattamenti).
	legal: {
		companyName: 'SpediamoFacile S.r.l.',
		vatNumber: '[INSERIRE_PIVA]',              // es. 'IT01234567890'
		fiscalCode: '[INSERIRE_CF]',               // se diverso da P.IVA
		registeredOffice: '[INSERIRE_SEDE_LEGALE]',// es. 'Via Roma 1, 00100 Roma (RM)'
		rea: '[INSERIRE_REA]',                     // es. 'RM-1234567'
		shareCapital: '[INSERIRE_CAPITALE]',       // es. '10.000,00 €'
		legalRepresentative: '[INSERIRE_RAPPRESENTANTE_LEGALE]',
		dpoName: '[INSERIRE_NOME_DPO]',            // se nominato (facoltativo PMI)
		dpoEmail: 'privacy@spediamofacile.it',     // contatto privacy di default
		supportEmail: 'info@spediamofacile.it',
		pec: '[INSERIRE_PEC]',                     // richiesta per società
	},
	ui: {
		icons: {
			arrowDown: 'i-mdi-arrow-down',
			arrowLeft: 'i-mdi-arrow-left',
			arrowRight: 'i-mdi-arrow-right',
			arrowUp: 'i-mdi-arrow-up',
			caution: 'i-mdi-alert-circle-outline',
			check: 'i-mdi-check',
			chevronDown: 'i-mdi-chevron-down',
			chevronLeft: 'i-mdi-chevron-left',
			chevronRight: 'i-mdi-chevron-right',
			chevronUp: 'i-mdi-chevron-up',
			close: 'i-mdi-close',
			copy: 'i-mdi-content-copy',
			error: 'i-mdi-close-circle-outline',
			info: 'i-mdi-information-outline',
			loading: 'i-mdi-loading',
			menu: 'i-mdi-menu',
			minus: 'i-mdi-minus',
			plus: 'i-mdi-plus',
			reload: 'i-mdi-refresh',
			search: 'i-mdi-magnify',
			success: 'i-mdi-check-circle-outline',
			warning: 'i-mdi-alert-outline',
		},
		accordion: {
			slots: {
				root: "w-full mt-[46px] mb-[88px] desktop:mt-[88px] desktop:mb-[54px] desktop-xl:mt-[126px] desktop-xl:mb-[72px]",
				item: "border-b-[1px] last:border-b-[1px] border-[#D0D0D0] mt-[30px] first:mt-0 desktop-xl:pb-[18px]",
				header: "flex pb-[15px]",
				/* trigger: "group flex-1 flex items-center gap-1.5 font-medium text-sm py-3.5 focus-visible:outline-primary min-w-0", */
				content: "data-[state=open]:animate-[accordion-down_200ms_ease-out] data-[state=closed]:animate-[accordion-up_200ms_ease-out] overflow-hidden focus:outline-none",
				body: "text-[rgba(64,64,64,.6)] text-[1.125rem] tracking-[-0.252px] leading-[160%] desktop-xl:max-w-[1122px]",
				/* leadingIcon: "shrink-0 size-5", */
				trailingIcon: "text-[#E44203] !size-6 shrink-0 desktop:!size-8 ms-auto group-data-[state=open]:rotate-180 transition-transform duration-200 cursor-pointer",
				label: "text-[1.25rem] desktop:text-[1.5rem] desktop-xl:text-[1.875rem] font-medium text-[#222222] leading-[110%] tracking-[-0.72px] cursor-pointer",
			},
			/* variants: {
				disabled: {
					true: {
						trigger: "cursor-not-allowed opacity-75",
					},
				},
			}, */
		},
		modal: {
			slots: {
				overlay: "fixed inset-0",
				content: "bg-[#E4E4E4] focus:outline-none font-montserrat",
				header: "block min-h-16 p-0 sm:px-0",
				wrapper: "",
				body: "p-[21px_0_0_0] sm:p-[21px_0_0_0]",
				footer: "flex items-center p-0",
				title: "text-[#252B42] font-bold text-[1.8125rem] tracking-[0.1px]",
				description: "text-[#252B42] mt-[20px] text-[0.9375rem] leading-[24px] tracking-[0.1px] text-center px-[10px]",
				close: "absolute top-[45px] right-[30px] bg-[url(/img/quote/second-step/close.png)] bg-center bg-no-repeat text-0 text-transparent hover:bg-transparent cursor-pointer focus:bg-transparent",
			},
			variants: {
				transition: {
					true: {
						overlay: "data-[state=open]:animate-[fade-in_200ms_ease-out] data-[state=closed]:animate-[fade-out_200ms_ease-in]",
						content: "data-[state=open]:animate-[scale-in_200ms_ease-out] data-[state=closed]:animate-[scale-out_200ms_ease-in]",
					},
				},
				fullscreen: {
					true: {
						content: "inset-0",
					},
					false: {
						content: "px-[35px] pt-[40px] pb-[31px] w-[calc(100vw-2rem)] rounded-lg shadow-lg ring ring-default rounded-[20px]",
					},
				},
				overlay: {
					true: {
						overlay: "bg-elevated/75",
					},
				},
				scrollable: {
					true: {
						overlay: "overflow-y-auto",
						content: "relative",
					},
					false: {
						content: "fixed",
						body: "overflow-y-auto",
					},
				},
			},
			compoundVariants: [
				{
					scrollable: true,
					fullscreen: false,
					class: {
						overlay: "grid place-items-center p-4 sm:py-8",
					},
				},
				{
					scrollable: false,
					fullscreen: false,
					class: {
						content: "top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 max-h-[calc(100dvh-2rem)] sm:max-h-[calc(100dvh-4rem)] overflow-hidden",
					},
				},
			],
		},
		tabs: {
			slots: {
				root: "flex items-center gap-2",
				list: "relative flex p-1 group",
				indicator: "absolute transition-[translate,width] duration-200",
				trigger: [
					"group relative inline-flex items-center min-w-0 data-[state=inactive]:text-muted hover:data-[state=inactive]:not-disabled:text-default font-medium rounded-md disabled:cursor-not-allowed disabled:opacity-75",
					"transition-colors",
				],
				leadingIcon: "shrink-0",
				leadingAvatar: "shrink-0",
				leadingAvatarSize: "",
				label: "truncate",
				trailingBadge: "shrink-0",
				trailingBadgeSize: "sm",
				content: "focus:outline-none w-full",
			},
			variants: {
				color: {
					primary: "",
					secondary: "",
					success: "",
					info: "",
					warning: "",
					error: "",
					neutral: "",
				},
				variant: {
					pill: {
						list: "bg-elevated rounded-lg",
						trigger: "grow",
						indicator: "rounded-md shadow-xs",
					},
					link: {
						list: "border-default",
						indicator: "rounded-full",
						trigger: "focus:outline-none focus-visible:outline-[3px] focus-visible:outline-[rgba(9,88,102,0.45)] focus-visible:outline-offset-2",
					},
				},
				orientation: {
					horizontal: {
						root: "flex-col",
						list: "w-full",
						indicator: "left-0 w-(--reka-tabs-indicator-size) translate-x-(--reka-tabs-indicator-position)",
						trigger: "justify-center",
					},
					vertical: {
						list: "flex-col",
						indicator: "top-0 h-(--reka-tabs-indicator-size) translate-y-(--reka-tabs-indicator-position)",
					},
				},
				size: {
					xs: {
						trigger: "px-2 py-1 text-xs gap-1",
						leadingIcon: "size-4",
						leadingAvatarSize: "3xs",
					},
					sm: {
						trigger: "px-2.5 py-1.5 text-xs gap-1.5",
						leadingIcon: "size-4",
						leadingAvatarSize: "3xs",
					},
					md: {
						trigger: "px-3 py-1.5 text-sm gap-1.5",
						leadingIcon: "size-5",
						leadingAvatarSize: "2xs",
					},
					lg: {
						trigger: "px-3 py-2 text-sm gap-2",
						leadingIcon: "size-5",
						leadingAvatarSize: "2xs",
					},
					xl: {
						trigger: "px-3 py-2 text-base gap-2",
						leadingIcon: "size-6",
						leadingAvatarSize: "xs",
					},
				},
			},
			compoundVariants: [
				{
					orientation: "horizontal",
					variant: "pill",
					class: {
						indicator: "inset-y-1",
					},
				},
				{
					orientation: "horizontal",
					variant: "link",
					class: {
						list: "border-b -mb-px",
						indicator: "-bottom-px h-px",
					},
				},
				{
					orientation: "vertical",
					variant: "pill",
					class: {
						indicator: "inset-x-1",
						list: "items-center",
					},
				},
				{
					orientation: "vertical",
					variant: "link",
					class: {
						list: "border-s -ms-px",
						indicator: "-start-px w-px",
					},
				},
				{
					color: "primary",
					variant: "pill",
					class: {
						indicator: "bg-[#005961]",
						trigger: "data-[state=active]:text-inverted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary",
					},
				},
				{
					color: "neutral",
					variant: "pill",
					class: {
						indicator: "bg-inverted",
						trigger: "data-[state=active]:text-inverted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-inverted",
					},
				},
				{
					color: "primary",
					variant: "link",
					class: {
						indicator: "bg-primary",
						trigger: "data-[state=active]:text-primary focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary",
					},
				},
				{
					color: "neutral",
					variant: "link",
					class: {
						indicator: "bg-inverted",
						trigger: "data-[state=active]:text-highlighted focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-inverted",
					},
				},
			],
			defaultVariants: {
				color: "primary",
				variant: "pill",
				size: "md",
			},
		},
		breadcrumb: {
			slots: {
				root: "relative min-w-0",
				list: "flex items-center gap-1.5",
				item: "flex min-w-0",
				link: "group relative flex items-center gap-1.5 text-sm min-w-0 focus-visible:outline-primary text-[1.3rem]",
				linkLeadingIcon: "shrink-0 size-5",
				linkLeadingAvatar: "shrink-0",
				linkLeadingAvatarSize: "2xs",
				linkLabel: "truncate",
				separator: "flex",
				separatorIcon: "shrink-0 size-5 text-black",
			},
			variants: {
				active: {
					true: {
						link: "text-[#005961] font-bold",
					},
					false: {
						link: "text-black font-normal hover:text-[var(--color-brand-primary)]",
					},
				},
				disabled: {
					true: {
						link: "cursor-auto",
					},
				},
				to: {
					true: "",
				},
			},
			compoundVariants: [
				{
					disabled: false,
					active: false,
					to: true,
					class: {
						link: ["hover:text-default", "transition-colors"],
					},
				},
			],
		},
		user: {
			slots: {
				root: "relative group/user",
				wrapper: "",
				name: "tracking-[-0.384px] text-[#525252]",
				description: "text-[rgba(82,82,82,.6)]",
				avatar: "shrink-0 w-[40px] h-[40px]",
			},
			variants: {
				size: {
					mio: {
						root: "gap-[10px]",
						wrapper: "",
						name: "text-[1rem]",
						description: "text-[0.875rem]",
					},
				},
			},
			defaultVariants: {
				size: "mio",
			},
		},
	},
});
