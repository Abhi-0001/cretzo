<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php /* html_escape(): $title is assembled from settings and, on some pages,
         from record names - all of which are user-editable somewhere. Escaping a
         static string costs nothing, and it means no future page that puts a
         product, seller or customer name in the title reopens an injection. */ ?>
    <title><?= html_escape($title) ?></title>
    <link rel="icon" href="<?= base_url() . get_settings('favicon') ?>" type="image/gif" sizes="16x16">
</head>

<body>
    <?php

    echo $contact_us;

    ?>
</body>

</html>