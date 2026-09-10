package com.koms.app.ui.student

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.koms.app.data.model.Dojo
import com.koms.app.databinding.ItemDojoBinding

class DojoAdapter(private var dojos: List<Dojo>) : RecyclerView.Adapter<DojoAdapter.DojoViewHolder>() {

    class DojoViewHolder(val binding: ItemDojoBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): DojoViewHolder {
        val binding = ItemDojoBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return DojoViewHolder(binding)
    }

    override fun onBindViewHolder(holder: DojoViewHolder, position: Int) {
        val dojo = dojos[position]
        holder.binding.tvDojoName.text = dojo.name
        holder.binding.tvLocation.text = dojo.location
        holder.binding.tvMasterName.text = "Sensei: ${dojo.masterName}"
        holder.binding.tvTimings.text = "${dojo.trainingDays ?: "Mon-Sat"} | ${dojo.trainingTimings ?: "6:00 PM - 8:00 PM"}"
    }

    override fun getItemCount() = dojos.size

    fun updateDojos(newDojos: List<Dojo>) {
        dojos = newDojos
        notifyDataSetChanged()
    }
}
