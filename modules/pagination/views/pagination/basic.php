<p class="pagination">
    
    <?php
    if ($first_page !== false): ?>
        <a href="<?php
        echo HTML::chars($page->url($first_page)) ?>" rel="first"><?php
            echo I18n::get('First') ?></a>
    <?php
    else: ?>
        <?php
        echo I18n::get('First') ?>
    <?php
    endif ?>
    
    <?php
    if ($previous_page !== false): ?>
        <a href="<?php
        echo HTML::chars($page->url($previous_page)) ?>" rel="prev"><?php
            echo I18n::get('Previous') ?></a>
    <?php
    else: ?>
        <?php
        echo I18n::get('Previous') ?>
    <?php
    endif ?>
    
    <?php
    for ($i = 1; $i <= $total_pages; $i++): ?>
        
        <?php
        if ($i == $current_page): ?>
            <strong><?php
                echo $i ?></strong>
        <?php
        else: ?>
            <a href="<?php
            echo HTML::chars($page->url($i)) ?>"><?php
                echo $i ?></a>
        <?php
        endif ?>
    
    <?php
    endfor ?>
    
    <?php
    if ($next_page !== false): ?>
        <a href="<?php
        echo HTML::chars($page->url($next_page)) ?>" rel="next"><?php
            echo I18n::get('Next') ?></a>
    <?php
    else: ?>
        <?php
        echo I18n::get('Next') ?>
    <?php
    endif ?>
    
    <?php
    if ($last_page !== false): ?>
        <a href="<?php
        echo HTML::chars($page->url($last_page)) ?>" rel="last"><?php
            echo I18n::get('Last') ?></a>
    <?php
    else: ?>
        <?php
        echo I18n::get('Last') ?>
    <?php
    endif ?>

</p><!-- .pagination -->
