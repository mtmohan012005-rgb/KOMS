package com.koms.app.ui.student

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.koms.app.data.model.Tournament
import com.koms.app.databinding.ItemTournamentBinding

class TournamentAdapter(private var list: List<Tournament>) : RecyclerView.Adapter<TournamentAdapter.ViewHolder>() {

    class ViewHolder(val binding: ItemTournamentBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = ItemTournamentBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val item = list[position]
        holder.binding.tvTournamentName.text = item.name
        holder.binding.tvEventDate.text = "Date: ${item.eventDate}"
        holder.binding.tvVenue.text = "Venue: ${item.venue}"
        holder.binding.tvDeadline.text = "Register by: ${item.registrationDeadline}"
    }

    override fun getItemCount() = list.size

    fun updateList(newList: List<Tournament>) {
        list = newList
        notifyDataSetChanged()
    }
}
