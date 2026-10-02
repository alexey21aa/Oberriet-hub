# One-time native history import

This manual-only workflow imports the already uploaded full Git bundle into the existing private repository. It merges the preserved branch with the upload commit, keeps both histories and performs a normal fast-forward push. It does not force-push, delete branches, use a personal token, contact other repositories or modify WordPress.

Before installation, review the exact workflow and authorize the job-scoped temporary `contents: write` permission. Install as `.github/workflows/import-existing-mvp.yml` through the owner UI and run it manually only if Actions usage is within the account's free allowance. After success, verify the original HEAD is an ancestor of GitHub main and that the source tree is visible. The bundle and source archive remain preserved.
