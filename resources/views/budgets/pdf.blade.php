<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <title>Orçamento {{ $budget->code }}</title>
  <style>
    body {
      font-family: Arial, sans-serif;
    }

    .header {
      text-align: center;
      margin-bottom: 20px;
    }

    .section {
      margin-bottom: 20px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th,
    td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: left;
    }

    th {
      background-color: #f2f2f2;
    }
  </style>
</head>

<body>
  <div class="header">
    <h1>Orçamento {{ $budget->code }}</h1>
    <p>Data: {{ $budget->created_at->format( 'd/m/Y' ) }}</p>
  </div>

  <div class="section">
    <h2>Cliente</h2>
    <p>{{ $budget->customer->common_data->first_name }} {{ $budget->customer->common_data->last_name }}</p>
    <p>{{ $budget->customer->contact->email }}</p>
  </div>

  <div class="section">
    <h2>Itens do Orçamento</h2>
    <table>
      <thead>
        <tr>
          <th>Produto</th>
          <th>Quantidade</th>
          <th>Valor Unitário</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        @foreach( $budget->services as $service )
          @foreach( $service->serviceItems as $item )
            <tr>
              <td>{{ $item->product->name }}</td>
              <td>{{ $item->quantity }}</td>
              <td>R$ {{ number_format( $item->unit_value, 2, ',', '.' ) }}</td>
              <td>R$ {{ number_format( $item->total, 2, ',', '.' ) }}</td>
            </tr>
          @endforeach
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="section">
    <h2>Total</h2>
    <p><strong>R$ {{ number_format( $budget->total, 2, ',', '.' ) }}</strong></p>
  </div>
</body>

</html>
