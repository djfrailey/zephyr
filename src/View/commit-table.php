<style>
	
.name {
	width: 200px;
}

.state {
	width: 104px;
	text-align: center;
}

.progress {
	width: 150px;
}

.team {
	width: auto;
}

.noOneWorks {
    text-align: center;
}

</style>

<table>
    <?php if ($data): ?>
        <?php $highest = reset($data); ?>
        <?php $highest = $highest['sum']; ?>
        <tr>
            <th class="name">Name</th>
            <th class="progress">Commits</th>
        </tr>
        <?php foreach($data as $user): ?>
            <tr>
                <td class="name"><?= htmlspecialchars($user['email_address']) ?></td>
                <td class="progress projectProgress blue"><div class="progressBarContainer"><span style="width: <?= round((floatval($user['sum']) / floatval($highest)) * 100) ?>%;"><center><?= round($user['sum'], 0) ?></center></span></div></td>
            </tr>
        <?php endforeach; ?>	
    <?php else: ?>
        <tr>
            <td class="noOneWorks">No One Works Here.</td>
        </tr>
    <?php endif; ?>
</table>