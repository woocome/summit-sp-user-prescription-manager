<?php

sp_upm_get_template_part('/emails/content', 'header');

sp_upm_get_template_part('/emails/content', 'body', $args);

sp_upm_get_template_part('/emails/content', 'footer');