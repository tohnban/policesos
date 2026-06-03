<?php
namespace Src\classes;

class ClassRender{

private $Dir;
private $Title;
private $Description;
private $Keywords;
private $Data = [];
private $Canonical = '';
private $OgTitle = '';
private $OgDescription = '';
private $OgImage = '';
private $OgType = 'website';
private $StructuredData = [];

public function getDir(){return $this->Dir;}
public function setDir($Dir){$this->Dir=$Dir;}


public function getTitle(){return $this->Title;}
public function setTitle($Title){$this->Title=$Title;}


public function getDescription(){return $this->Description;}
public function setDescription($Description){$this->Description=$Description;}


public function getKeywords(){return $this->Keywords;}
public function setKeywords($Keywords){$this->Keywords=$Keywords;}

public function getCanonical(){return $this->Canonical;}
public function setCanonical($Canonical){$this->Canonical=$Canonical; return $this;}

public function getOgTitle(){return $this->OgTitle ?: $this->Title;}
public function setOgTitle($OgTitle){$this->OgTitle=$OgTitle; return $this;}

public function getOgDescription(){return $this->OgDescription ?: $this->Description;}
public function setOgDescription($OgDescription){$this->OgDescription=$OgDescription; return $this;}

public function getOgImage(){return $this->OgImage;}
public function setOgImage($OgImage){$this->OgImage=$OgImage; return $this;}

public function getOgType(){return $this->OgType;}
public function setOgType($OgType){$this->OgType=$OgType; return $this;}

public function getStructuredData(){return $this->StructuredData;}
public function setStructuredData($data){
	if(is_array($data)) {
		$this->StructuredData = array_merge($this->StructuredData, $data);
	}
	return $this;
}

public function addStructuredData($data){
	if(is_array($data)) {
		if(empty($this->StructuredData)) {
			$this->StructuredData = $data;
		} else {
			// If StructuredData is already an array, create a graph
			if(!isset($this->StructuredData['@graph'])) {
				$this->StructuredData = [
					'@context' => 'https://schema.org',
					'@graph' => [$this->StructuredData]
				];
			}
			$this->StructuredData['@graph'][] = $data;
		}
	}
	return $this;
}

public function setData($data){
	if(is_array($data)) {
		$this->Data = array_merge($this->Data, $data);
	}
	return $this;
}

	#Para Renderizar o Layout
	public function renderLayout()
	{
		include_once(DIRREQ."app/view/Layout.php");
	}
	public function renderLayoutApi()
	{
		include_once(DIRREQ."app/view/LayoutApi.php");
	}

	#Para Adicionar caracteristicas especificas no head
	public function addHead()
	{
		if (file_exists(DIRREQ."app/view/{$this->getDir()}/Head.php")) {
			extract($this->Data);
			include(DIRREQ."app/view/{$this->getDir()}/Head.php");
		}
	}
	#Para Adicionar caracteristicas especificas no header
	public function addHeader()
	{
		if (file_exists(DIRREQ."app/view/{$this->getDir()}/Header.php")) {
			extract($this->Data);
			include(DIRREQ."app/view/{$this->getDir()}/Header.php");
		}
	}
	#Para Adicionar caracteristicas especificas no main
	public function addMain(){
		$mainPath = DIRREQ."app/view/{$this->getDir()}/Main.php";
		if (file_exists($mainPath)) {
			extract($this->Data);
			include($mainPath);
		} else {
			// Fallback para debug
			echo "<!-- ERROR: Main.php not found at: {$mainPath} -->";
		}
	}
	#Para Adicionar Outros Componentes
	public function addComponent(){
		if (file_exists(DIRREQ."app/view/{$this->getDir()}/Component.php")) {
			extract($this->Data);
			include(DIRREQ."app/view/{$this->getDir()}/Component.php");
		}
	}
	#Para Adicionar caracteristicas especificas no footer
	public function addFooter(){
		if (file_exists(DIRREQ."app/view/{$this->getDir()}/Footer.php")) {
			extract($this->Data);
			include(DIRREQ."app/view/{$this->getDir()}/Footer.php");
		}
	}
}
?>