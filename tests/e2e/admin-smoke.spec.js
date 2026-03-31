const { test, expect } = require("@playwright/test");

test("bootstrap admin can log in and open the admin dashboard", async ({ page }) => {
	const username = process.env.E2E_BOOTSTRAP_ADMIN_USERNAME || "admin";
	const password = process.env.E2E_BOOTSTRAP_ADMIN_PASSWORD || "admin123456";

	await page.goto("/login.html");
	await expect(page).toHaveURL(/\/login\.html$/);

	await page.getByLabel(/username/i).fill(username);
	await page.getByLabel(/password/i).fill(password);
	await page.getByRole("button").click();

	await page.goto("/admin/index.html");
	await expect(page).toHaveURL(/\/admin\/index\.html$/);
	await expect(page.locator("body.admin-layout")).toBeVisible();
	await expect(page.getByRole("link", { name: /log out/i })).toBeVisible();
	await expect(page.getByText("Welcome to Radaptor App")).toBeVisible();

	await page.goto("/");
	await expect(page).toHaveURL(/\/$/);
	await expect(page.getByText("Your application skeleton is installed and ready.")).toBeVisible();
});
