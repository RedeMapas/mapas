<div class="ficha-spcultura"> 
    <h3><?php \MapasCulturais\i::_e("Dados Pessoais");?></h3>
    <?php if($this->isEditable() && $entity->shortDescription && strlen($entity->shortDescription) > 2000): ?>
        <div class="alert warning">
            <?php \MapasCulturais\i::_e("O limite de caracteres da descrição curta foi diminuido para 400, mas seu texto atual possui");?>
            <?php echo strlen($entity->shortDescription) ?>
            <?php \MapasCulturais\i::_e("caracteres. Você deve alterar seu texto ou este será cortado ao salvar.");?>
        </div>
    <?php endif; ?>
    <span class="js-editable <?php echo ($entity->isPropertyRequired($entity,"shortDescription") && $editEntity? 'required': '');?>" data-edit="shortDescription" data-original-title="<?php \MapasCulturais\i::esc_attr_e("Descrição Curta");?>" data-emptytext="<?php \MapasCulturais\i::esc_attr_e("Insira uma descrição curta");?>" data-showButtons="bottom" data-tpl='<textarea maxlength="400"></textarea>'><?php echo $this->isEditable() ? $entity->shortDescription : nl2br($entity->shortDescription); ?></span>

    <?php $this->applyTemplateHook('tab-about-service','before'); ?><!--. hook tab-about-service:before -->

    <div class="servico">
        <?php $this->applyTemplateHook('tab-about-service','begin'); ?><!--. hook tab-about-service:begin -->

        <?php if($this->isEditable() || $entity->site): ?>
            <p><span class="label"><?php \MapasCulturais\i::_e("Site");?>:</span>
            <?php if($this->isEditable()): ?>
                <span class="js-editable <?php echo ($entity->isPropertyRequired($entity,"site") && $editEntity? 'required': '');?>" data-edit="site" data-original-title="<?php \MapasCulturais\i::esc_attr_e("Site");?>" data-emptytext="<?php \MapasCulturais\i::esc_attr_e("Insira a url de seu site");?>"><?php echo $entity->site; ?></span></p>
            <?php else: ?>
                <a class="url" href="<?php echo $entity->site; ?>"><?php echo $entity->site; ?></a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if($this->isEditable()): ?>
            <!-- Campo Nome Completo -->
            <p class="privado">
                <span class="icon icon-private-info"></span>
                <span class="label"><?php \MapasCulturais\i::_e("Nome Completo");?>:</span>
                <span class="js-editable <?php echo ($entity->isPropertyRequired($entity,"nomeCompleto") && $editEntity? 'required': '');?>" data-edit="nomeCompleto" data-original-title="<?php \MapasCulturais\i::esc_attr_e("Nome Completo ou Razão Social");?>" data-emptytext="<?php \MapasCulturais\i::esc_attr_e("Informe seu nome completo ou razão social");?>">
                    <?php echo $entity->nomeCompleto; ?>
                </span>
            </p>
            <!-- Campo CPF -->
            <p class="privado">
                <span class="icon icon-private-info"></span>
                <span class="label"><?php \MapasCulturais\i::_e("CPF");?>:</span>
                <span class="js-editable <?php echo ($entity->isPropertyRequired($entity,"documento") && $editEntity? 'required': '');?>" data-edit="documento" data-original-title="<?php \MapasCulturais\i::esc_attr_e("CPF");?>" data-emptytext="<?php \MapasCulturais\i::esc_attr_e("Informe seu CPF  com pontos, hífens e barras");?>">
                    <?php echo $entity->documento; ?>
                </span>
            </p>
           
           
            
            <!-- E-mail privado-->
            <p class="privado"><span class="icon icon-private-info"></span>
                <span class="label"><?php \MapasCulturais\i::_e("Email Privado");?>:</span>
                <span class="js-editable <?php echo ($entity->isPropertyRequired($entity,"emailPrivado") && $editEntity? 'required': '');?>" data-edit="emailPrivado" data-original-title="<?php \MapasCulturais\i::esc_attr_e("Email Privado");?>" data-emptytext="<?php \MapasCulturais\i::esc_attr_e("Insira um email que não será exibido publicamente");?>">
                    <?php echo $entity->emailPrivado; ?>
                </span>
            </p>
        <?php endif; ?>

        <!-- Email Público -->
        <?php if($this->isEditable() || $entity->emailPublico): ?>
            <p><span class="label"><?php \MapasCulturais\i::_e("E-mail");?>:</span>
                <span class="js-editable <?php echo ($entity->isPropertyRequired($entity,"emailPublico") && $this->isEditable()? 'required': '');?>" data-edit="emailPublico" data-original-title="<?php \MapasCulturais\i::esc_attr_e("Email Público");?>" data-emptytext="<?php \MapasCulturais\i::esc_attr_e("Insira um email que será exibido publicamente");?>">
                    <?php echo $entity->emailPublico; ?>
                </span>
            </p>
        <?php endif; ?>

        <!-- Email Público -->
        <?php if($this->isEditable() || $entity->telefonePublico): ?>
            <p><span class="label"><?php \MapasCulturais\i::_e("Telefone Público");?>:</span>
                <span class="js-editable js-mask-phone <?php echo ($entity->isPropertyRequired($entity,"telefonePublico") && $this->isEditable()? 'required': '');?>" data-edit="telefonePublico" data-original-title="<?php \MapasCulturais\i::esc_attr_e("Telefone Público");?>" data-emptytext="<?php \MapasCulturais\i::esc_attr_e("Insira um telefone que será exibido publicamente");?>">
                    <?php echo $entity->telefonePublico; ?>
                </span>
            </p>
        <?php endif; ?>

        <?php if($this->isEditable()): ?>
            <!-- Telefone Privado 1 -->
            <p class="privado"><span class="icon icon-private-info"></span>
                <span class="label"><?php \MapasCulturais\i::_e("Telefone 1");?>:</span>
                <span class="js-editable js-mask-phone <?php echo ($entity->isPropertyRequired($entity,"telefone1") && $this->isEditable()? 'required': '');?>" data-edit="telefone1" data-original-title="<?php \MapasCulturais\i::esc_attr_e("Telefone Privado");?>" data-emptytext="<?php \MapasCulturais\i::esc_attr_e("Insira um telefone que não será exibido publicamente");?>">
                    <?php echo $entity->telefone1; ?>
                </span>
            </p>
            <!-- Telefone Privado 2 -->
            <p class="privado"><span class="icon icon-private-info"></span>
                <span class="label"><?php \MapasCulturais\i::_e("Telefone 2");?>:</span>
                <span class="js-editable js-mask-phone <?php echo ($entity->isPropertyRequired($entity,"telefone2") && $this->isEditable()? 'required': '');?>" data-edit="telefone2" data-original-title="<?php \MapasCulturais\i::esc_attr_e("Telefone Privado");?>" data-emptytext="<?php \MapasCulturais\i::esc_attr_e("Insira um telefone que não será exibido publicamente");?>">
                    <?php echo $entity->telefone2; ?>
                </span>
            </p>
        <?php endif; ?>
        <input type="hidden" class="js-editable" id="endereco" data-edit="endereco" data-original-title="<?php \MapasCulturais\i::esc_attr_e("Endereço");?>" data-emptytext="<?php \MapasCulturais\i::esc_attr_e("Insira o endereço");?>" data-showButtons="bottom" value="<?php echo $entity->endereco ?>" data-value="<?php echo $entity->endereco ?>">
            <p class="endereco"><span class="label"><?php \MapasCulturais\i::_e("Endereço");?>:</span> <span class="js-endereco"><?php echo $entity->endereco ?></span></p>
            <p><span class="label"><?php \MapasCulturais\i::_e("CEP");?>:</span> <span class="js-editable js-mask-cep" id="En_CEP" data-edit="En_CEP" data-original-title="<?php \MapasCulturais\i::esc_attr_e("CEP");?>" data-emptytext="<?php \MapasCulturais\i::esc_attr_e("Insira o CEP");?>" data-showButtons="bottom"><?php echo $entity->En_CEP ?></span></p>
            <p><span class="label"><?php \MapasCulturais\i::_e("Logradouro");?>:</span> <span class="js-editable" id="En_Nome_Logradouro" data-edit="En_Nome_Logradouro" data-original-title="<?php \MapasCulturais\i::esc_attr_e("Logradouro");?>" data-emptytext="<?php \MapasCulturais\i::esc_attr_e("Insira o logradouro");?>" data-showButtons="bottom"><?php echo $entity->En_Nome_Logradouro ?></span></p>
            <p><span class="label"><?php \MapasCulturais\i::_e("Número");?>:</span> <span class="js-editable" id="En_Num" data-edit="En_Num" data-original-title="<?php \MapasCulturais\i::esc_attr_e("Número");?>" data-emptytext="<?php \MapasCulturais\i::esc_attr_e("Insira o Número");?>" data-showButtons="bottom"><?php echo $entity->En_Num ?></span></p>
            <p><span class="label"><?php \MapasCulturais\i::_e("Complemento");?>:</span> <span class="js-editable" id="En_Complemento" data-edit="En_Complemento" data-original-title="<?php \MapasCulturais\i::esc_attr_e("Complemento");?>" data-emptytext="<?php \MapasCulturais\i::esc_attr_e("Insira um complemento");?>" data-showButtons="bottom"><?php echo $entity->En_Complemento ?></span></p>
            <p><span class="label"><?php \MapasCulturais\i::_e("Bairro");?>:</span> <span class="js-editable" id="En_Bairro" data-edit="En_Bairro" data-original-title="<?php \MapasCulturais\i::esc_attr_e("Bairro");?>" data-emptytext="<?php \MapasCulturais\i::esc_attr_e("Insira o Bairro");?>" data-showButtons="bottom"><?php echo $entity->En_Bairro ?></span></p>
            <p><span class="label"><?php \MapasCulturais\i::_e("Município");?>:</span> <span class="js-editable" id="En_Municipio" data-edit="En_Municipio" data-original-title="<?php \MapasCulturais\i::esc_attr_e("Município");?>" data-emptytext="<?php \MapasCulturais\i::esc_attr_e("Insira o Município");?>" data-showButtons="bottom"><?php echo $entity->En_Municipio ?></span></p>
            <p><span class="label"><?php \MapasCulturais\i::_e("Estado");?>:</span> <span class="js-editable" id="En_Estado" data-edit="En_Estado" data-original-title="<?php \MapasCulturais\i::esc_attr_e("Estado");?>" data-emptytext="<?php \MapasCulturais\i::esc_attr_e("Insira o Estado");?>" data-showButtons="bottom"><?php echo $entity->En_Estado ?></span></p>
            <?php if($this->isEditable()): ?>
                <p class="privado">
                    <span class="icon icon-private-info"></span><span class="label"><?php \MapasCulturais\i::_e("Localização");?>:</span>
                    <span class="js-editable clear" data-edit="publicLocation" data-type="select" data-showbuttons="false"
                        data-value="<?php echo $entity->publicLocation ? '1' : '0';?>"
                        <?php /* Translators: Location public / private */ ?>
                        data-source="[{value: 1, text: '<?php \MapasCulturais\i::esc_attr_e("Pública");?>'},{value: 0, text:'<?php \MapasCulturais\i::esc_attr_e("Privada");?>'}]">
                    </span>
                </p>
            <?php endif; ?>
        <?php $this->applyTemplateHook('tab-about-service','end'); ?><!--. hook tab-about-service:end -->
    </div><!--.servico -->

    <?php $this->applyTemplateHook('tab-about-service','after'); ?><!--. hook tab-about-service:after -->

</div>
<div class="ficha-spcultura"> 
    <h3><?php \MapasCulturais\i::_e("Dados Pessoais Sensíveis");?></h3>
    <div class="servico">
    <!-- Campo Data de Nascimento  -->
    <p class="privado">
        <span class="icon icon-private-info"></span>
        <span class="label"><?php \MapasCulturais\i::_e("Data de Nascimento");?>:</span>
        <span class="js-editable <?php echo ($entity->isPropertyRequired($entity,"dataDeNascimento") && $this->isEditable()? 'required': '');?>" <?php echo $entity->dataDeNascimento ? "data-value='".$entity->dataDeNascimento . "'" : ''?>  data-type="date" data-edit="dataDeNascimento" data-viewformat="dd/mm/yyyy" data-showbuttons="false" data-original-title="<?php \MapasCulturais\i::esc_attr_e("Data de Nascimento");?>" data-emptytext="<?php \MapasCulturais\i::esc_attr_e("Insira a data de nascimento");?>">
        <?php $dtN = (new DateTime)->createFromFormat('Y-m-d', $entity->dataDeNascimento); echo $dtN ? $dtN->format('d/m/Y') : ''; ?>
        </span>
    </p>
    <!-- Gênero -->
    <p class="privado">
        <span class="icon icon-private-info"></span>
        <span class="label"><?php \MapasCulturais\i::_e("Gênero");?>:</span>
        <span class="js-editable <?php echo ($entity->isPropertyRequired($entity,"genero") && $editEntity? 'required': '');?>" data-edit="genero" data-original-title="<?php \MapasCulturais\i::esc_attr_e("Gênero");?>" data-emptytext="<?php \MapasCulturais\i::esc_attr_e("Selecione o gênero se for pessoa física");?>">
            <?php echo $entity->genero; ?>
        </span>
    </p>
    
    <!-- Orientação Sexual -->
    <p class="privado"><span class="icon icon-private-info"></span>
        <span class="label"><?php \MapasCulturais\i::_e("Orientação Sexual");?>:</span>
        <span class="js-editable <?php echo ($entity->isPropertyRequired($entity,"orientacaoSexual") && $editEntity? 'required': '');?>" data-edit="orientacaoSexual" data-original-title="<?php \MapasCulturais\i::esc_attr_e("Orientação Sexual");?>" data-emptytext="<?php \MapasCulturais\i::esc_attr_e("Selecione uma orientação sexual se for pessoa física");?>">
            <?php echo $entity->orientacaoSexual; ?>
        </span>
    </p>
        <!-- Raça/Cor -->
        <p class="privado"><span class="icon icon-private-info"></span>
        <span class="label"><?php \MapasCulturais\i::_e("Raça/Cor");?>:</span>
        <span class="js-editable  <?php echo ($entity->isPropertyRequired($entity,"raca") && $editEntity? 'required': '');?>" data-edit="raca" data-original-title="<?php \MapasCulturais\i::esc_attr_e("Raça/cor");?>" data-emptytext="<?php \MapasCulturais\i::esc_attr_e("Selecione a raça/cor se for pessoa física");?>">
            <?php echo $entity->raca; ?>
        </span>
        </p>
        </div>
</div>