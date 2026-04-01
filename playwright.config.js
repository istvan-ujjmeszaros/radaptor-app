// @ts-check
const { defineConfig } = require("@playwright/test");

module.exports = defineConfig({
	testDir: "./tests/e2e",
	timeout: 60_000,
	expect: {
		timeout: 10_000,
	},
	fullyParallel: false,
	workers: 1,
	reporter: [["list"], ["html", { open: "never" }]],
	use: {
		baseURL: process.env.E2E_BASE_URL || "http://localhost",
		trace: "retain-on-failure",
		screenshot: "only-on-failure",
		video: "retain-on-failure",
	},
});
