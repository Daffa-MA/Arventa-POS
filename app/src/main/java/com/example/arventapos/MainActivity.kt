    package com.example.arventapos

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateMapOf
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import com.example.arventapos.ui.theme.ArventaPOSTheme
import java.text.NumberFormat
import java.util.Locale
import kotlin.math.roundToInt

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContent {
            ArventaPOSTheme {
                PosScreen()
            }
        }
    }
}

data class StoreSetting(
    val storeName: String,
    val businessType: String,
    val themeColor: Color,
    val appLayout: String,
    val productCardStyle: String,
    val showSku: Boolean,
    val showStock: Boolean,
    val taxRate: Double,
    val serviceChargeRate: Double,
    val receiptFooter: String,
)

data class PosItem(
    val id: Int,
    val name: String,
    val sku: String?,
    val type: String,
    val unit: String,
    val price: Int,
    val stock: Double?,
)

private val demoSetting = StoreSetting(
    storeName = "Arventa Demo Store",
    businessType = "Retail & Service",
    themeColor = Color(0xFF2563EB),
    appLayout = "grid",
    productCardStyle = "minimal",
    showSku = false,
    showStock = true,
    taxRate = 11.0,
    serviceChargeRate = 0.0,
    receiptFooter = "Terima kasih sudah berbelanja.",
)

private val demoItems = listOf(
    PosItem(1, "Kopi Susu Arventa", "ARV-KOPI-001", "Produk", "ml", 18_000, 10_000.0),
    PosItem(2, "Paket Cuci Motor", "ARV-SVC-001", "Layanan", "pcs", 25_000, null),
    PosItem(3, "Roti Bakar", "ARV-FOOD-001", "Produk", "gram", 15_000, 5_000.0),
    PosItem(4, "Kain Premium", "ARV-FAB-001", "Produk", "meter", 42_000, 80.5),
)

@Composable
fun PosScreen(
    setting: StoreSetting = demoSetting,
    items: List<PosItem> = demoItems,
) {
    val cart = remember { mutableStateMapOf<Int, Int>() }
    val subtotal = cart.entries.sumOf { (id, qty) -> (items.first { it.id == id }.price * qty) }
    val tax = (subtotal * setting.taxRate / 100).roundToInt()
    val service = (subtotal * setting.serviceChargeRate / 100).roundToInt()
    val total = subtotal + tax + service

    Scaffold(
        modifier = Modifier.fillMaxSize(),
        topBar = {
            StoreHeader(setting)
        },
        bottomBar = {
            CheckoutBar(
                subtotal = subtotal,
                tax = tax,
                service = service,
                total = total,
                footer = setting.receiptFooter,
                themeColor = setting.themeColor,
            )
        },
    ) { innerPadding ->
        ProductCatalog(
            setting = setting,
            items = items,
            cart = cart,
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding)
                .background(Color(0xFFF8FAFC))
                .padding(16.dp),
        )
    }
}

@Composable
private fun ProductCatalog(
    setting: StoreSetting,
    items: List<PosItem>,
    cart: MutableMap<Int, Int>,
    modifier: Modifier = Modifier,
) {
    val addItem = { item: PosItem -> cart[item.id] = (cart[item.id] ?: 0) + 1 }
    val removeItem = { item: PosItem ->
        val next = (cart[item.id] ?: 0) - 1
        if (next <= 0) cart.remove(item.id) else cart[item.id] = next
    }

    if (setting.appLayout == "grid") {
        LazyVerticalGrid(
            columns = GridCells.Adaptive(minSize = 168.dp),
            modifier = modifier,
            verticalArrangement = Arrangement.spacedBy(10.dp),
            horizontalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            item {
                Text(
                    text = "Produk & Layanan",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.SemiBold,
                )
            }
            items(items) { item ->
                ProductTile(
                    item = item,
                    quantity = cart[item.id] ?: 0,
                    setting = setting,
                    onAdd = { addItem(item) },
                    onRemove = { removeItem(item) },
                )
            }
        }
    } else {
        LazyColumn(
            modifier = modifier,
            verticalArrangement = Arrangement.spacedBy(if (setting.appLayout == "compact") 6.dp else 10.dp),
        ) {
            item {
                Text(
                    text = "Produk & Layanan",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.SemiBold,
                )
            }
            items(items) { item ->
                ProductRow(
                    item = item,
                    quantity = cart[item.id] ?: 0,
                    setting = setting,
                    compact = setting.appLayout == "compact",
                    onAdd = { addItem(item) },
                    onRemove = { removeItem(item) },
                )
            }
        }
    }
}

@Composable
private fun StoreHeader(setting: StoreSetting) {
    Surface(color = setting.themeColor, shadowElevation = 2.dp) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp, vertical = 18.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Box(
                modifier = Modifier
                    .size(42.dp)
                    .background(Color.White.copy(alpha = 0.18f), RoundedCornerShape(8.dp)),
                contentAlignment = Alignment.Center,
            ) {
                Text("A", color = Color.White, fontWeight = FontWeight.Bold)
            }
            Spacer(Modifier.width(12.dp))
            Column {
                Text(setting.storeName, color = Color.White, style = MaterialTheme.typography.titleLarge)
                Text(setting.businessType, color = Color.White.copy(alpha = 0.82f), style = MaterialTheme.typography.bodySmall)
            }
        }
    }
}

@Composable
private fun ProductRow(
    item: PosItem,
    quantity: Int,
    setting: StoreSetting,
    compact: Boolean,
    onAdd: () -> Unit,
    onRemove: () -> Unit,
) {
    Card(
        shape = RoundedCornerShape(8.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        modifier = Modifier.fillMaxWidth(),
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .clickable(onClick = onAdd)
                .padding(if (compact) 10.dp else 14.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Column(modifier = Modifier.weight(1f)) {
                Text(item.name, fontWeight = FontWeight.SemiBold)
                Text(productMeta(item, setting), color = Color(0xFF64748B), style = MaterialTheme.typography.bodySmall)
                Spacer(Modifier.height(6.dp))
                Text("${formatRupiah(item.price)} / ${item.unit}", color = setting.themeColor, fontWeight = FontWeight.Bold)
            }
            Row(verticalAlignment = Alignment.CenterVertically) {
                Button(onClick = onRemove, enabled = quantity > 0) {
                    Text("-")
                }
                Text(
                    text = quantity.toString(),
                    modifier = Modifier.padding(horizontal = 12.dp),
                    fontWeight = FontWeight.Bold,
                )
                Button(onClick = onAdd) {
                    Text("+")
                }
            }
        }
    }
}

@Composable
private fun ProductTile(
    item: PosItem,
    quantity: Int,
    setting: StoreSetting,
    onAdd: () -> Unit,
    onRemove: () -> Unit,
) {
    Card(
        shape = RoundedCornerShape(8.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        modifier = Modifier.clickable(onClick = onAdd),
    ) {
        Column(Modifier.padding(12.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            if (setting.productCardStyle == "image") {
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(70.dp)
                        .background(setting.themeColor.copy(alpha = 0.12f), RoundedCornerShape(8.dp)),
                    contentAlignment = Alignment.Center,
                ) {
                    Text(item.name.take(1), color = setting.themeColor, fontWeight = FontWeight.Bold)
                }
            }
            Text(item.name, fontWeight = FontWeight.SemiBold)
            Text(productMeta(item, setting), color = Color(0xFF64748B), style = MaterialTheme.typography.bodySmall)
            Text("${formatRupiah(item.price)} / ${item.unit}", color = setting.themeColor, fontWeight = FontWeight.Bold)
            Row(verticalAlignment = Alignment.CenterVertically) {
                Button(onClick = onRemove, enabled = quantity > 0) {
                    Text("-")
                }
                Text(quantity.toString(), modifier = Modifier.padding(horizontal = 10.dp), fontWeight = FontWeight.Bold)
                Button(onClick = onAdd) {
                    Text("+")
                }
            }
        }
    }
}

@Composable
private fun CheckoutBar(
    subtotal: Int,
    tax: Int,
    service: Int,
    total: Int,
    footer: String,
    themeColor: Color,
) {
    Surface(color = Color.White, shadowElevation = 8.dp) {
        Column(Modifier.fillMaxWidth().padding(16.dp)) {
            SummaryLine("Subtotal", subtotal)
            SummaryLine("Pajak", tax)
            SummaryLine("Service", service)
            Spacer(Modifier.height(8.dp))
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text("Total", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                Text(formatRupiah(total), style = MaterialTheme.typography.titleMedium, color = themeColor, fontWeight = FontWeight.Bold)
            }
            Spacer(Modifier.height(10.dp))
            Button(onClick = {}, enabled = total > 0, modifier = Modifier.fillMaxWidth()) {
                Text("Bayar")
            }
            Text(footer, modifier = Modifier.fillMaxWidth().padding(top = 8.dp), color = Color(0xFF64748B), style = MaterialTheme.typography.bodySmall)
        }
    }
}

@Composable
private fun SummaryLine(label: String, value: Int) {
    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
        Text(label, color = Color(0xFF64748B), style = MaterialTheme.typography.bodySmall)
        Text(formatRupiah(value), style = MaterialTheme.typography.bodySmall)
    }
}

private fun formatRupiah(value: Int): String {
    return NumberFormat.getCurrencyInstance(Locale.forLanguageTag("id-ID")).format(value).replace(",00", "")
}

private fun productMeta(item: PosItem, setting: StoreSetting): String {
    val parts = mutableListOf(item.type)

    if (setting.showSku && item.sku != null) {
        parts += item.sku
    }

    if (setting.showStock) {
        parts += item.stock?.let { "Stok ${formatQuantity(it)} ${item.unit}" } ?: "Tanpa stok"
    }

    return parts.joinToString(" | ")
}

private fun formatQuantity(value: Double): String {
    return if (value % 1.0 == 0.0) {
        value.toInt().toString()
    } else {
        String.format(Locale.US, "%.3f", value).trimEnd('0').trimEnd('.')
    }
}

@Preview(showBackground = true)
@Composable
fun PosScreenPreview() {
    ArventaPOSTheme {
        PosScreen()
    }
}
