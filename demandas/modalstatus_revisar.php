<!--------- MODAL ENTREGAR --------->
<div class="modal" id="revisarModal" tabindex="-1" role="dialog" aria-labelledby="revisarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <!-- lucas 22092023 ID 358 Modificado titulo do modal-->
                <h5 class="modal-title" id="exampleModalLabel">Chamado - <?php echo $demanda['tituloDemanda'] ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <div class="container-fluid p-0">
                        <!-- lucas 27022024 - id853 nova chamada editor quill -->
                        <div id="ql-toolbarRevisar">
                            <?php include ROOT."/sistema/quilljs/ql-toolbar-min.php"  ?>
                            <input type="file" id="anexarRevisar" class="custom-file-upload" name="nomeAnexo" onchange="uploadFileRevisar()" style=" display:none">
                            <label for="anexarRevisar">
                                <a class="btn p-0 ms-1"><i class="bi bi-paperclip"></i></a>
                            </label>
                        </div>
                        <div id="ql-editorRevisar" style="height:30vh !important">
                        </div>
                        <textarea style="display: none" id="quill-revisar" name="comentario"></textarea>
                    </div>
                    <div class="col-md">
                        <input type="hidden" class="form-control" name="idDemanda" value="<?php echo $demanda['idDemanda'] ?>" readonly>
                        <input type="hidden" class="form-control" name="idCliente" value="<?php echo $demanda['idCliente'] ?>" readonly>
                        <input type="hidden" class="form-control" name="idUsuario" value="<?php echo $usuario['idUsuario'] ?>" readonly>
                        <input type="hidden" class="form-control" name="tipoStatusDemanda" value="<?php echo $demanda['idTipoStatus'] ?>" readonly>
                        <input type="hidden" class="form-control" name="origem" value="<?php echo $origem ?>" readonly>
                        <?php if (isset($url_idTipoContrato[2])) { ?>
                            <input type="hidden" class="form-control" name="idTipoContrato" value="<?php echo $url_idTipoContrato[2] ?>" readonly>
                        <?php } ?>
                    </div>

            </div>
            <div class="modal-footer">
                <?php if ($_SESSION['administradora'] == 1) { ?>
                    <div class="mt-2 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="interno" id="interno" value="1" checked>
                        <label class="form-check-label" for="interno">Interno</label>
                    </div>
                <?php } ?>
                <!-- lucas 22092023 ID 358 Modificado nome do botao-->
                <button type="submit" formaction="../database/demanda.php?operacao=atualizar&acao=revisar" class="btn btn-info">Revisão</button>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- lucas 27022024 - id853 nova chamada editor quill -->
<!-- gabriel 27052024 - id981 removido modalstatus.js para evitar redundancia do script -->
<script>
var quillRevisar = new Quill('#ql-editorRevisar', {
    modules: {
        toolbar: '#ql-toolbarRevisar'
    },
    placeholder: 'Digite o texto...',
    theme: 'snow'
});

quillRevisar.on('text-change', function(delta, oldDelta, source) {
    $('#quill-revisar').val(quillRevisar.container.firstChild.innerHTML);
});

async function uploadFileRevisar() {

    let endereco = '/tmp/';
    let formData = new FormData();
    var custombutton = document.getElementById("anexarRevisar");
    var arquivo = custombutton.files[0]["name"];

    formData.append("arquivo", custombutton.files[0]);
    formData.append("endereco", endereco);

    destino = endereco + arquivo;

    await fetch('/sistema/quilljs/quill-uploadFile.php', {
        method: "POST",
        body: formData
    });

    const range = this.quillRevisar.getSelection(true)

    this.quillRevisar.insertText(range.index, arquivo, 'user');
    this.quillRevisar.setSelection(range.index, arquivo.length);
    this.quillRevisar.theme.tooltip.edit('link', destino);
    this.quillRevisar.theme.tooltip.save();

    this.quillRevisar.setSelection(range.index + destino.length);

}
</script>