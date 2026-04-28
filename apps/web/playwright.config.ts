import { execSync } from 'node:child_process';
import { defineConfig, devices } from '@playwright/test';

const firstValue = (...values: Array<string | undefined | null>) =>
	values.map((value) => value?.trim()).find((value): value is string => Boolean(value)) || '';

const normalizeURL = (value: string) => value.replace(/\/+$/, '');

const resolveWslGatewayBaseURL = () => {
	if (!process.env.WSL_DISTRO_NAME && !process.env.WSL_INTEROP) {
		return '';
	}

	const gateway = firstValue(
		process.env.WSL_HOST_GATEWAY,
		(() => {
			try {
				return execSync("ip route show default | awk '/default/ {print $3; exit}'", {
					encoding: 'utf8',
					stdio: ['ignore', 'pipe', 'ignore'],
				}).trim();
			} catch {
				return '';
			}
		})(),
	);

	return gateway ? `http://${gateway}:8787` : '';
};

const defaultBaseURL = normalizeURL(firstValue(resolveWslGatewayBaseURL(), 'http://127.0.0.1:8787'));

const baseURL = normalizeURL(firstValue(process.env.PLAYWRIGHT_BASE_URL, process.env.TEST_BASE_URL, defaultBaseURL));

const storageState = firstValue(process.env.PLAYWRIGHT_STORAGE_STATE, process.env.TEST_STORAGE_STATE);

const webServerCommand = firstValue(
	process.env.PLAYWRIGHT_WEB_SERVER_COMMAND,
	process.env.TEST_WEB_SERVER_COMMAND,
	baseURL.includes(':3001') ? 'npm run dev' : '',
);

const webServerURL = normalizeURL(
	firstValue(process.env.PLAYWRIGHT_WEB_SERVER_URL, process.env.TEST_WEB_SERVER_URL, webServerCommand ? baseURL : ''),
);

export default defineConfig({
	testDir: './tests/e2e',
	fullyParallel: true,
	forbidOnly: !!process.env.CI,
	retries: process.env.CI ? 2 : 0,
	workers: process.env.CI ? 1 : undefined,
	reporter: 'html',

	// default snapshot tolerance per visual regression suite.
	// Override per-test con maxDiffPixels/threshold se necessario.
	expect: {
		toHaveScreenshot: {
			threshold: 0.001,
			maxDiffPixels: 100,
			animations: 'disabled',
			caret: 'hide',
		},
	},

	use: {
		baseURL,
		...(storageState ? { storageState } : {}),
		trace: 'on-first-retry',
		screenshot: 'only-on-failure',
	},

	projects: [
		{
			name: 'chromium',
			use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 900 } },
		},
		{
			name: 'mobile-chrome',
			use: { ...devices['Pixel 5'], viewport: { width: 375, height: 812 } },
		},
		{
			name: 'tablet',
			use: {
				viewport: { width: 768, height: 1024 },
				userAgent: devices['Desktop Chrome'].userAgent,
			},
		},
	],

	webServer: webServerCommand
		? {
				command: webServerCommand,
				url: webServerURL || baseURL,
				reuseExistingServer: !process.env.CI,
				timeout: Number(process.env.PLAYWRIGHT_WEB_SERVER_TIMEOUT || process.env.TEST_WEB_SERVER_TIMEOUT || 120000),
			}
		: undefined,
});
