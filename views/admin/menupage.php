<div class="wrap">
    <h1>Schriftarten verwalten</h1>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="save_aj_vinyl_fonts">
        <?php wp_nonce_field('aj_vinyl_save_fonts', 'aj_vinyl_fonts_nonce'); ?>

        <h2>Neue Schriftart hinzufügen</h2>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">Schriftartname</th>
                    <td><input type="text" name="new_font[name]" required /></td>
                </tr>
                <tr>
                    <th scope="row">Bold</th>
                    <td><input type="checkbox" name="new_font[bold]" value="1" /></td>
                </tr>
                <tr>
                    <th scope="row">Italic</th>
                    <td><input type="checkbox" name="new_font[italic]" value="1" /></td>
                </tr>
            </tbody>
        </table>
        <?php submit_button('Schriftart hinzufügen'); ?>
    </form>

    <h2>Gespeicherte Schriftarten</h2>
    <ul>
        <?php if (!empty($fonts)) : ?>
            <?php foreach ($fonts as $index => $font) : ?>
                <li>
                    <?php echo esc_html($font['name']); ?> (Bold: <?php echo $font['bold'] ? 'Ja' : 'Nein'; ?>, Italic: <?php echo $font['italic'] ? 'Ja' : 'Nein'; ?>)

                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                        <?php wp_nonce_field('aj_vinyl_delete_fonts', 'aj_vinyl_fonts_nonce'); ?>
                        <input type="hidden" name="action" value="delete_aj_vinyl_font">
                        <input type="hidden" name="index" value="<?php echo $index; ?>">
                        <input type="submit" class="button" value="Löschen" onclick="return confirm('Bist du sicher, dass du diese Schriftart löschen möchtest?');" />
                    </form>

                    <button class="edit-font" data-index="<?php echo $index; ?>" data-name="<?php echo esc_attr($font['name']); ?>" data-bold="<?php echo $font['bold'] ? '1' : '0'; ?>" data-italic="<?php echo $font['italic'] ? '1' : '0'; ?>">Bearbeiten</button>
                </li>
            <?php endforeach; ?>
        <?php else : ?>
            <li>Keine Schriftarten gespeichert.</li>
        <?php endif; ?>
    </ul>
</div>

<!-- Modal für das Bearbeiten von Schriftarten -->
<div id="edit-font-modal" style="display:none;">
    <h2>Schriftart bearbeiten</h2>
    <form id="edit-font-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="update_aj_vinyl_font">
        <?php wp_nonce_field('aj_vinyl_update_fonts', 'aj_vinyl_fonts_nonce'); ?>

        <input type="hidden" name="index" id="edit-font-index" value="">
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">Schriftartname</th>
                    <td><input type="text" name="font[name]" id="edit-font-name" required /></td>
                </tr>
                <tr>
                    <th scope="row">Bold</th>
                    <td><input type="checkbox" name="font[bold]" id="edit-font-bold" value="1" /></td>
                </tr>
                <tr>
                    <th scope="row">Italic</th>
                    <td><input type="checkbox" name="font[italic]" id="edit-font-italic" value="1" /></td>
                </tr>
            </tbody>
        </table>
        <input type="submit" class="button" value="Ändern">
        <button type="button" class="button" id="close-edit-modal">Abbrechen</button>
    </form>
</div>

<script>
    document.querySelectorAll('.edit-font').forEach(button => {
        button.addEventListener('click', () => {
            const index = button.getAttribute('data-index');
            const name = button.getAttribute('data-name');
            const bold = button.getAttribute('data-bold');
            const italic = button.getAttribute('data-italic');

            document.getElementById('edit-font-index').value = index;
            document.getElementById('edit-font-name').value = name;
            document.getElementById('edit-font-bold').checked = bold === '1';
            document.getElementById('edit-font-italic').checked = italic === '1';

            document.getElementById('edit-font-modal').style.display = 'block';
        });
    });

    document.getElementById('close-edit-modal').addEventListener('click', () => {
        document.getElementById('edit-font-modal').style.display = 'none';
    });
</script>
