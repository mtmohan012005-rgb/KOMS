package com.koms.app.ui.student

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.koms.app.data.model.FeeRecord
import com.koms.app.databinding.ItemFeeBinding
import java.util.Locale

class FeeAdapter(private var list: List<FeeRecord>) : RecyclerView.Adapter<FeeAdapter.ViewHolder>() {

    class ViewHolder(val binding: ItemFeeBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = ItemFeeBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val item = list[position]
        holder.binding.tvFeeName.text = item.feeName
        holder.binding.tvDueDate.text = "Due: ${item.dueDate} (${item.billingMonth})"
        holder.binding.tvAmount.text = "$${String.format(Locale.ROOT, "%.2f", item.amountDue)}"
        holder.binding.tvStatus.text = item.status.uppercase()
    }

    override fun getItemCount() = list.size

    fun updateList(newList: List<FeeRecord>) {
        list = newList
        notifyDataSetChanged()
    }
}
