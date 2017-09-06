<?php

$pluginifrs_menu = ' <ul class="pluginifrs_menu">
        <li><a href="https://moodle.ifrs.edu.br/report/sistec/index.php?id='.$_GET['id'].'">Início</a></li>
        <li><a href="#">Relatórios</a>
            <ul>
              <li><a href="https://moodle.ifrs.edu.br/report/sistec/page_sistec.php?id='.$_GET['id'].'">SISTEC</a></li>
              <li><a href="https://moodle.ifrs.edu.br/report/sistec/page_cursosvazios.php?id='.$_GET['id'].'">Cursos Vazios</a></li>
              <li><a href="https://moodle.ifrs.edu.br/report/sistec/page_servidores.php?id='.$_GET['id'].'">Servidores</a></li> 
              <li><a href="https://moodle.ifrs.edu.br/report/sistec/page_professoread.php?id='.$_GET['id'].'">Professor EaD</a></li>                    
            </ul>
        </li>
    <li><a href="#">Ações</a>
        <ul>
          <li><a href="https://moodle.ifrs.edu.br/report/sistec/page_buscaporcpf.php?id='.$_GET['id'].'">Buscar por CPF</a></li>
          <li><a href="https://moodle.ifrs.edu.br/report/sistec/page_confirmar.php?id='.$_GET['id'].'">Confirmar Usuários</a></li>                 
        </ul>
    </li>
    <li><a href="page_about?id='.$_GET['id'].'"">Sobre</a></li>
</ul>';