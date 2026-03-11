<!DOCTYPE html>
<html>
<head>
<title>Color By Number Generator</title>

<style>

body{
font-family:Arial;
background:#f2f4f8;
text-align:center;
padding:60px;
}

.card{
background:white;
padding:40px;
border-radius:10px;
width:420px;
margin:auto;
box-shadow:0 10px 30px rgba(0,0,0,0.15);
}

input,select{
padding:10px;
margin:8px;
width:80%;
}

button{
padding:12px 30px;
background:#2d7ef7;
border:none;
color:white;
border-radius:6px;
font-size:16px;
cursor:pointer;
}

</style>
</head>

<body>

<div class="card">

<h2>Color-By-Number Worksheet Generator</h2>

<form action="process.php" method="post" enctype="multipart/form-data">

<input type="file" name="image" required><br>

<label>Grid Size</label>
<select name="grid">
<option value="20">Easy (20x20)</option>
<option value="30">Medium (30x30)</option>
<option value="50">Hard (50x50)</option>
<option value="75">Expert (75x75)</option>
<option value="100">Ultimate (100x100)</option>
</select>

<br>

<label>Color Palette</label>
<select name="colors">
<option value="6">6 Colors</option>
<option value="8">8 Colors</option>
<option value="12">12 Colors</option>
<option value="16">16 Colors</option>
</select>

<br><br>

<button>Generate Worksheet</button>

</form>

</div>

</body>
</html>