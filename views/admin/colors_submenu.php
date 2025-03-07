<div class="wrap">
    <h1>Farben verwalten</h1>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="save_aj_vinyl_colors">
        <?php wp_nonce_field('aj_vinyl_save_colors', 'aj_vinyl_colors_nonce'); ?>

        <h2>Neue Farbe hinzufügen</h2>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">Farbenname</th>
                    <td><input type="text" name="new_color[color]" required /></td>
                </tr>
                <tr>
                    <th scope="row">Finish</th>
                    <td><input type="text" name="new_color[finish]" placeholder="z.B. glänzend, matt, seidenmatt" required /></td>
                </tr>
            </tbody>
        </table>
        <?php submit_button('Farbe hinzufügen'); ?>
    </form>

    <h2>Gespeicherte Farben</h2>
    <ul>
        <?php if (!empty($colors)) : ?>
            <?php foreach ($colors as $index => $color) : ?>
                <li>
                    <?php echo esc_html($color['color']); ?> (Finish: <?php echo esc_html($color['finish']); ?>)

                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                        <?php wp_nonce_field('aj_vinyl_delete_colors', 'aj_vinyl_colors_nonce'); ?>
                        <input type="hidden" name="action" value="delete_aj_vinyl_color">
                        <input type="hidden" name="index" value="<?php echo $index; ?>">
                        <input type="submit" class="button" value="Löschen" onclick="return confirm('Bist du sicher, dass du diese Farbe löschen möchtest?');" />
                    </form>

                    <button class="edit-color" data-index="<?php echo $index; ?>" data-color="<?php echo esc_attr($color['color']); ?>" data-finish="<?php echo esc_attr($color['finish']); ?>">Bearbeiten</button>
                </li>
            <?php endforeach; ?>
        <?php else : ?>
            <li>Keine Farben gespeichert.</li>
        <?php endif; ?>
    </ul>
</div>

<!-- Modal für das Bearbeiten von Farben -->
<div id="edit-color-modal" style="display:none;">
    <h2>Farbe bearbeiten</h2>
    <form id="edit-color-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="update_aj_vinyl_color">
        <?php wp_nonce_field('aj_vinyl_update_colors', 'aj_vinyl_colors_nonce'); ?>

        <input type="hidden" name="index" id="edit-color-index" value="">
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">Farbenname</th>
                    <td><input type="text" name="color[color]" id="edit-color-name" required /></td>
                </tr>
                <tr>
                    <th scope="row">Finish</th>
                    <td><input type="text" name="color[finish]" id="edit-color-finish" placeholder="z.B. glänzend, matt" required /></td>
                </tr>
            </tbody>
        </table>
        <input type="submit" class="button" value="Ändern">
        <button type="button" class="button" id="close-edit-modal">Abbrechen</button>
    </form>
</div>

<script>
    document.querySelectorAll('.edit-color').forEach(button => {
        button.addEventListener('click', () => {
            const index = button.getAttribute('data-index');
            const color = button.getAttribute('data-color');
            const finish = button.getAttribute('data-finish');

            document.getElementById('edit-color-index').value = index;
            document.getElementById('edit-color-name').value = color;  // Hier wird color verwendet
            document.getElementById('edit-color-finish').value = finish;

            document.getElementById('edit-color-modal').style.display = 'block';
        });
    });

    document.getElementById('close-edit-modal').addEventListener('click', () => {
        document.getElementById('edit-color-modal').style.display = 'none';
    });
</script>
