<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Submissions - <?= htmlspecialchars($tenantId) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Form Submissions</h1>
            <div class="header-actions">
                <span class="tenant-badge">Tenant: <?= htmlspecialchars($tenantId) ?></span>
                <a href="dashboard.php?logout=1" class="btn btn-secondary">Logout</a>
            </div>
        </header>

        <div class="filters">
            <form method="GET" action="dashboard.php" class="filter-form">
                <select name="form" onchange="this.form.submit()">
                    <option value="">All Forms</option>
                    <?php foreach ($formNames as $name): ?>
                        <option value="<?= htmlspecialchars($name) ?>" <?= $currentForm === $name ? 'selected' : '' ?>>
                            <?= htmlspecialchars($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <div class="stats">
                Total: <?= $totalCount ?> submissions
            </div>
        </div>

        <?php if (empty($submissions)): ?>
            <div class="empty-state">
                <p>No submissions yet.</p>
            </div>
        <?php else: ?>
            <div class="submissions-list">
                <?php foreach ($submissions as $submission): ?>
                    <div class="submission-card">
                        <div class="submission-header">
                            <span class="submission-id">#<?= $submission['id'] ?></span>
                            <span class="submission-form"><?= htmlspecialchars($submission['form_name']) ?></span>
                            <span class="submission-date"><?= date('M j, Y g:i A', strtotime($submission['created_at'])) ?></span>
                        </div>
                        <div class="submission-body">
                            <table class="data-table">
                                <?php foreach (($submission['form_data'] ?? []) as $key => $value): ?>
                                    <tr>
                                        <th><?= htmlspecialchars($key) ?></th>
                                        <td>
                                            <?php if (is_array($value)): ?>
                                                <?= htmlspecialchars(json_encode($value)) ?>
                                            <?php else: ?>
                                                <?= nl2br(htmlspecialchars((string) $value)) ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        <div class="submission-meta">
                            <span title="IP Address">IP: <?= htmlspecialchars($submission['sender_ip'] ?? 'N/A') ?></span>
                            <?php if (!empty($submission['referer_url'])): ?>
                                <span title="Referer">From: <?= htmlspecialchars(parse_url($submission['referer_url'], PHP_URL_HOST) ?? $submission['referer_url']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?><?= $currentForm ? '&form=' . urlencode($currentForm) : '' ?>"
                           class="page-link <?= $currentPage === $i ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
