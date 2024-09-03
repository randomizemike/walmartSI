<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingInstructionsResource\Pages;
use App\Filament\Resources\ShippingInstructionsResource\RelationManagers;
use App\Models\ShippingInstructions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Wizard; 
use Illuminate\Support\Facades\Mail; // Import the Mail facade
use Illuminate\Support\Facades\Http; // Import the HTTP client

class ShippingInstructionsResource extends Resource
{
    protected static ?string $model = ShippingInstructions::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
        //->extraAttributes(['class' => 'flex items-center justify-center min-h-screen'])
        ->schema([
            Forms\Components\Wizard::make([
                Wizard\Step::make('Enter PO')
                    ->schema([
                        Forms\Components\TextInput::make('PONumber')
                            ->label('PO Number')
                            ->placeholder('Enter the PO Number for this shipment')
                            ->required(),
                    ]),
                Wizard\Step::make('Enter Containers')
                    ->schema([
                        Forms\Components\Repeater::make('Containers')
                            ->required()
                            ->schema([
                                Forms\Components\TextInput::make('ContainerNumber')
                                    ->label('Container Number')
                                    ->placeholder('e.g., ABCD1234567')
                                    ->required()
                                    ->rules(['regex:/^[A-Z]{4}[0-9]{7}$/']) // Regex rule for container number
                                    ->helperText('The container number must be 4 letters followed by 7 digits.'),
                                Forms\Components\TextInput::make('SealNumber')
                                    ->label('Seal Number')
                                    ->placeholder('Enter the Seal Number')
                                    ->required(),
                                Forms\Components\TextInput::make('VGM')
                                    ->label('VGM')
                                    ->numeric()
                                    ->required()
                                    ->extraAttributes(['class' => 'comma-format'])
                                    ->placeholder('Enter the Verified Gross Mass (VGM)')
                                    ->minValue(0)  // Optional: Set a minimum value
                                    ->step(0.1),   // Optional: Set the step for decimals
                                Forms\Components\Select::make('ContainerSize')
                                    ->label('Container Size')
                                    ->options([
                                        '20FT' => '20FT',
                                        '40FT' => '40FT',
                                        '40FT HC' => '40FT HC',
                                        '45FT' => '45FT',
                                    ])
                                    ->placeholder('Select the Container Size')
                                    ->required(),
                                Forms\Components\TextInput::make('SKU_No')
                                    ->label('SKU No / Assortment No / Item No')
                                    ->placeholder('Enter the SKU No / Assortment No / Item No')
                                    ->required(),
                                Forms\Components\TextInput::make('Quantity')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->required()
                                    ->placeholder('Enter the Quantity')
                                    ->extraAttributes(['class' => 'comma-format'])
                                    ->minValue(0)  // Optional: Set a minimum value
                                    ->step(1),   // Optional: Set the step for decimals
                                Forms\Components\TextInput::make('CBM')
                                    ->label('Cargo Dimensions (CBM)')
                                    ->numeric()
                                    ->required()
                                    ->placeholder('Enter the Cargo Dimensions (CBM)')
                                    ->extraAttributes(['class' => 'comma-format'])
                                    ->minValue(0)  // Optional: Set a minimum value
                                    ->step(1),   // Optional: Set the step for decimals
                                Forms\Components\DatePicker::make('shipping_date')
                                    ->label('Shipping Date')
                                    ->required()
                                    ->placeholder('Select the Shipping Date')
                                    ->displayFormat('Y-m-d'),
                                Forms\Components\TextInput::make('hscode')
                                    ->label('HS Code')
                                    ->required()
                                    ->placeholder('Enter the HS Code'),
                                Forms\Components\TextInput::make('commodity')
                                    ->label('Commodity Description')
                                    ->required()
                                    ->placeholder('Enter the Commodity Description'),
                            ]),
                    ]),
            ])
            //->extraAttributes(['class' => 'flex items-center justify-center h-full']), // Centering the wizard
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->columns([
            Tables\Columns\TextColumn::make('PONumber')
                ->label('PO Number')
                ->sortable()
                ->searchable(),
    
            Tables\Columns\TextColumn::make('Containers')
                ->label('Containers')
                ->getStateUsing(function ($record) {
                    return collect($record->Containers)->map(function ($container) {
                        return "Number: {$container['ContainerNumber']}, Seal: {$container['SealNumber']}, VGM: {$container['VGM']}";
                    })->implode('<br>'); // Join with line breaks
                })->html(), // Enable HTML rendering to allow line breaks
            ])
            ->filters([
                // Add filters if needed
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('sendSI')
                ->label('Send SI')
                ->button() // You can change this to 'link' if you prefer a link-style action
                ->action(function ($record) {
                    // Construct the JSON payload for the email body
                    $payload = [
                        '__metadata' => [
                            'type' => 'SP.Data.YourListNameListItem', // Replace 'YourListName' with your actual SharePoint list name
                        ],
                        'Title' => $record->PONumber, // Assuming 'Title' is a required field in SharePoint list
                        'PONumber' => $record->PONumber,
                        'Containers' => json_encode(collect($record->Containers)->map(function ($container) {
                            return [
                                'ContainerNumber' => $container['ContainerNumber'],
                                'SealNumber' => $container['SealNumber'],
                                'VGM' => $container['VGM'],
                                'ContainerSize' => $container['ContainerSize'],
                                'SKU_No' => $container['SKU_No'],
                                'Quantity' => $container['Quantity'],
                                'CBM' => $container['CBM'],
                                'ShippingDate' => $container['shipping_date'],
                                'HSCode' => $container['hscode'],
                                'CommodityDescription' => $container['commodity'],
                            ];
                        })->toArray()), // Convert containers to JSON string if needed
                    ];

                    // Convert the payload to a JSON string for the email body
                    $jsonPayload = json_encode($payload, JSON_PRETTY_PRINT);

                    // Send email
                    Mail::raw($jsonPayload, function ($message) use ($record) {
                        $message->to('mouad.fatich@gmail.com') // Replace with the actual recipient email
                                ->subject('Shipping Instruction for PO Number ' . $record->PONumber)
                                ->from(config('mail.from.address'), config('mail.from.name')); // Uses from config in .env
                    });

                    // Update status in the database to 'sent'
                    $record->si_status = 'sent';
                    $record->save();

                    // Optionally, add a success notification
                    session()->flash('success', 'Shipping instruction sent successfully via email.');
                })
                ->disabled(function ($record) {
                    return $record->si_status === 'sent'; // Disable if already sent
                }),
                ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageShippingInstructions::route('/'),
        ];
    }
}
